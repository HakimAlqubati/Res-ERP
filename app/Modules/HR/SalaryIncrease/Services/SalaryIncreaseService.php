<?php

declare(strict_types=1);

namespace App\Modules\HR\SalaryIncrease\Services;

use App\Models\AppLog;
use App\Models\Employee;
use App\Models\EmployeeSalaryLog;
use App\Models\SalaryIncreaseRequest;
use App\Modules\HR\Payroll\Contracts\EmployeeSalaryResolverInterface;
use App\Modules\HR\SalaryIncrease\Builders\SalaryIncreasePayloadBuilder;
use App\Modules\HR\SalaryIncrease\Contracts\EmployeeSalaryLogServiceInterface;
use App\Modules\HR\SalaryIncrease\Contracts\SalaryIncreaseRepositoryInterface;
use App\Modules\HR\SalaryIncrease\Contracts\SalaryIncreaseServiceInterface;
use App\Modules\HR\SalaryIncrease\DTOs\SalaryIncreaseDataDTO;
use App\Modules\HR\SalaryIncrease\DTOs\SalaryIncreaseFilterDTO;
use App\Modules\HR\SalaryIncrease\Notifications\SalaryIncreaseNotifier;
use App\Modules\HR\SalaryIncrease\Rules\SalaryIncreaseValidationRule;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

final class SalaryIncreaseService implements SalaryIncreaseServiceInterface
{
    public function __construct(
        private readonly SalaryIncreaseRepositoryInterface $repository,
        private readonly SalaryIncreaseValidationRule $validator,
        private readonly SalaryIncreaseNotifier $notifier,
        private readonly EmployeeSalaryLogServiceInterface $salaryLogService,
        private readonly EmployeeSalaryResolverInterface $salaryResolver,
    ) {}

    public function list(SalaryIncreaseFilterDTO $filter, int $perPage = 15): LengthAwarePaginator
    {
        return $this->repository->paginate($filter, $perPage);
    }

    public function getById(int $id): SalaryIncreaseRequest
    {
        return $this->repository->findByIdOrFail($id);
    }

    public function create(SalaryIncreaseDataDTO $data): SalaryIncreaseRequest
    {
        return DB::transaction(function () use ($data) {
            $employee = Employee::lockForUpdate()->findOrFail($data->employeeId);

            $builder = SalaryIncreasePayloadBuilder::make()
                ->forEmployee($employee)
                ->withData($data, $this->salaryResolver);

            $payload = $builder->buildCreatePayload();

            $this->validator->assertCanCreateOrUpdate(
                employee: $employee,
                calculatedAmounts: $payload,
                hasExistingPending: $this->repository->hasPendingForEmployee($employee->id)
            );

            $created = $this->repository->create($payload);

            AppLog::write(
                message: "Salary increase request #{$created->id} created for Employee {$employee->name} (#{$employee->id}): {$created->old_salary} -> {$created->new_salary}",
                level: AppLog::LEVEL_INFO,
                context: 'HR_SALARY_INCREASE_CREATED',
                extra: [
                    'request_id'     => $created->id,
                    'employee_id'    => $employee->id,
                    'old_salary'     => $created->old_salary,
                    'new_salary'     => $created->new_salary,
                    'effective_date' => $created->effective_date?->toDateString(),
                ]
            );

            DB::afterCommit(fn () => $this->notifier->notifySubmitted($created));

            return $created->load(['employee:id,name,employee_no,branch_id,salary', 'branch:id,name', 'createdBy:id,name']);
        });
    }

    public function update(int $id, SalaryIncreaseDataDTO $data): SalaryIncreaseRequest
    {
        return DB::transaction(function () use ($id, $data) {
            $lockedRequest = $this->repository->findByIdForUpdate($id);
            $this->validator->assertIsPending($lockedRequest);

            $employee = Employee::lockForUpdate()->findOrFail($lockedRequest->employee_id);

            $builder = SalaryIncreasePayloadBuilder::make()
                ->forEmployee($employee)
                ->withData($data, $this->salaryResolver);

            $payload = $builder->buildUpdatePayload();

            $this->validator->assertCanCreateOrUpdate(
                employee: $employee,
                calculatedAmounts: $payload,
                ignoreRequestId: $lockedRequest->id,
                hasExistingPending: $this->repository->hasPendingForEmployee($employee->id, $lockedRequest->id)
            );

            $updated = $this->repository->update($lockedRequest, $payload);

            return $updated->load(['employee:id,name,employee_no,branch_id,salary', 'branch:id,name', 'createdBy:id,name']);
        });
    }

    public function approve(int $id, ?int $approverId = null): SalaryIncreaseRequest
    {
        return DB::transaction(function () use ($id, $approverId) {
            $lockedRequest = $this->repository->findByIdForUpdate($id);
            $this->validator->assertCanTransition($lockedRequest, SalaryIncreaseRequest::STATUS_APPROVED);

            $lockedEmployee = Employee::lockForUpdate()->findOrFail($lockedRequest->employee_id);

            $approvalPayload = SalaryIncreasePayloadBuilder::make()
                ->buildApprovalPayload($lockedRequest, $lockedEmployee, $approverId, $this->salaryResolver);

            $this->validator->assertCanCreateOrUpdate(
                employee: $lockedEmployee,
                calculatedAmounts: $approvalPayload,
                allowMultiplePending: true
            );

            // 1. Persist final old_salary snapshot & approval metadata
            $approvedRequest = $this->repository->update($lockedRequest, $approvalPayload);

            // 2. Record into hr_employee_salary_logs (and sync hr_employees.salary only if effective_from <= current month)
            $effectiveDateStr = Carbon::parse($approvedRequest->effective_date ?? now())->toDateString();

            $this->salaryLogService->recordSalaryChange(
                employee: $lockedEmployee,
                newSalary: (float) $approvedRequest->new_salary,
                effectiveDate: $effectiveDateStr,
                source: EmployeeSalaryLog::SOURCE_SALARY_INCREASE_REQUEST,
                requestId: (int) $approvedRequest->id,
                notes: $approvedRequest->notes,
                actorId: $approverId ?? auth()->id(),
            );

            AppLog::write(
                message: "Salary increase request #{$approvedRequest->id} approved for Employee {$lockedEmployee->name} (#{$lockedEmployee->id}): {$approvedRequest->old_salary} -> {$approvedRequest->new_salary}",
                level: AppLog::LEVEL_INFO,
                context: 'HR_SALARY_INCREASE_APPROVED',
                extra: [
                    'request_id'     => $approvedRequest->id,
                    'employee_id'    => $lockedEmployee->id,
                    'old_salary'     => $approvedRequest->old_salary,
                    'new_salary'     => $approvedRequest->new_salary,
                    'effective_date' => $effectiveDateStr,
                    'approved_by'    => $approvedRequest->approved_by,
                ]
            );

            DB::afterCommit(fn () => $this->notifier->notifyDecision($approvedRequest));

            return $approvedRequest->load(['employee:id,name,employee_no,branch_id,salary', 'branch:id,name', 'approvedBy:id,name']);
        });
    }

    public function reject(int $id, string $rejectionReason, ?int $rejectorId = null): SalaryIncreaseRequest
    {
        return DB::transaction(function () use ($id, $rejectionReason, $rejectorId) {
            $lockedRequest = $this->repository->findByIdForUpdate($id);
            $this->validator->assertCanTransition($lockedRequest, SalaryIncreaseRequest::STATUS_REJECTED);

            $payload = SalaryIncreasePayloadBuilder::make()
                ->buildRejectionPayload($rejectionReason, $rejectorId);

            $rejectedRequest = $this->repository->update($lockedRequest, $payload);

            AppLog::write(
                message: "Salary increase request #{$rejectedRequest->id} rejected for Employee #{$rejectedRequest->employee_id}",
                level: AppLog::LEVEL_INFO,
                context: 'HR_SALARY_INCREASE_REJECTED',
                extra: [
                    'request_id'       => $rejectedRequest->id,
                    'employee_id'      => $rejectedRequest->employee_id,
                    'rejection_reason' => $rejectionReason,
                ]
            );

            DB::afterCommit(fn () => $this->notifier->notifyDecision($rejectedRequest));

            return $rejectedRequest->load(['employee:id,name,employee_no,branch_id,salary', 'branch:id,name', 'rejectedBy:id,name']);
        });
    }

    public function delete(int $id): bool
    {
        return DB::transaction(function () use ($id) {
            $lockedRequest = $this->repository->findByIdForUpdate($id);
            $this->validator->assertIsPending($lockedRequest);

            return $this->repository->delete($lockedRequest);
        });
    }
}
