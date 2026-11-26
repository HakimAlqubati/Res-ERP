<?php

declare(strict_types=1);

namespace App\Modules\HR\SalaryIncrease\Builders;

use App\Models\Employee;
use App\Models\SalaryIncreaseRequest;
use App\Modules\HR\Payroll\Contracts\EmployeeSalaryResolverInterface;
use App\Modules\HR\SalaryIncrease\DTOs\SalaryIncreaseDataDTO;
use Carbon\Carbon;
use InvalidArgumentException;

/**
 * Fluent Builder responsible for normalizing salary increase calculations
 * and constructing persistence arrays consistently across API, Filament, and Services.
 */
final class SalaryIncreasePayloadBuilder
{
    private ?Employee $employee = null;
    private ?float $oldSalary = null;
    private bool $hasExplicitBaseSalary = false;
    private string $increaseType = SalaryIncreaseRequest::INCREASE_TYPE_FIXED;
    private float $increaseAmount = 0.0;
    private ?float $increasePercentage = null;
    private float $newSalary = 0.0;
    private ?string $effectiveDate = null;
    private ?string $notes = null;
    private ?int $actorId = null;

    public static function make(): self
    {
        return new self();
    }

    public function forEmployee(Employee $employee): self
    {
        $clone = clone $this;
        $clone->employee = $employee;
        $clone->oldSalary = round((float) ($employee->salary ?? 0), 2);
        $clone->hasExplicitBaseSalary = false;

        return $clone;
    }

    public function withBaseSalary(float $salary): self
    {
        $clone = clone $this;
        $clone->oldSalary = round(max(0, $salary), 2);
        $clone->hasExplicitBaseSalary = true;

        return $clone;
    }

    public function withData(SalaryIncreaseDataDTO $dto, ?EmployeeSalaryResolverInterface $resolver = null): self
    {
        if (! $this->employee) {
            throw new InvalidArgumentException('Employee must be set before applying DTO data.');
        }

        $effectiveDate = $dto->effectiveDate ?: now()->toDateString();
        $parsedDate = Carbon::parse($effectiveDate);

        $baseSalary = $this->hasExplicitBaseSalary
            ? ($this->oldSalary ?? 0.0)
            : round(
                ($resolver ?? app(EmployeeSalaryResolverInterface::class))
                    ->resolveForPeriod($this->employee, $parsedDate->year, $parsedDate->month),
                2
            );

        $calculated = self::calculateAmounts(
            oldSalary: $baseSalary,
            increaseType: $dto->increaseType,
            value: $dto->value
        );

        $clone = clone $this;
        $clone->oldSalary = $calculated['old_salary'];
        $clone->increaseType = $dto->increaseType;
        $clone->increaseAmount = $calculated['increase_amount'];
        $clone->increasePercentage = $calculated['increase_percentage'];
        $clone->newSalary = $calculated['new_salary'];
        $clone->effectiveDate = $parsedDate->toDateString();
        $clone->notes = $dto->notes;
        $clone->actorId = $dto->actorId ?? auth()->id();

        return $clone;
    }

    /**
     * Single source of truth for calculating salary delta and target salary.
     *
     * @return array{old_salary: float, increase_amount: float, increase_percentage: float|null, new_salary: float}
     */
    public static function calculateAmounts(float $oldSalary, string $increaseType, float $value): array
    {
        $oldSalary = round(max(0, $oldSalary), 2);

        switch ($increaseType) {
            case SalaryIncreaseRequest::INCREASE_TYPE_PERCENTAGE:
                $percentage = round($value, 2);
                $amount = round($oldSalary * ($percentage / 100), 2);
                $newSalary = round($oldSalary + $amount, 2);
                break;

            case SalaryIncreaseRequest::INCREASE_TYPE_NEW_SALARY:
                $newSalary = round($value, 2);
                $amount = round($newSalary - $oldSalary, 2);
                $percentage = $oldSalary > 0 ? round(($amount / $oldSalary) * 100, 2) : null;
                break;

            case SalaryIncreaseRequest::INCREASE_TYPE_FIXED:
            default:
                $amount = round($value, 2);
                $newSalary = round($oldSalary + $amount, 2);
                $percentage = $oldSalary > 0 ? round(($amount / $oldSalary) * 100, 2) : null;
                break;
        }

        return [
            'old_salary'          => $oldSalary,
            'increase_amount'     => $amount,
            'increase_percentage' => $percentage,
            'new_salary'          => $newSalary,
        ];
    }

    public function buildCreatePayload(): array
    {
        if (! $this->employee) {
            throw new InvalidArgumentException('Employee is required to build create payload.');
        }

        return [
            'employee_id'         => $this->employee->id,
            'branch_id'           => $this->employee->branch_id,
            'old_salary'          => $this->oldSalary,
            'increase_type'       => $this->increaseType,
            'increase_amount'     => $this->increaseAmount,
            'increase_percentage' => $this->increasePercentage,
            'new_salary'          => $this->newSalary,
            'effective_date'      => $this->effectiveDate,
            'notes'               => $this->notes,
            'status'              => SalaryIncreaseRequest::STATUS_PENDING,
            'created_by'          => $this->actorId,
            'updated_by'          => $this->actorId,
        ];
    }

    public function buildUpdatePayload(): array
    {
        return [
            'old_salary'          => $this->oldSalary,
            'increase_type'       => $this->increaseType,
            'increase_amount'     => $this->increaseAmount,
            'increase_percentage' => $this->increasePercentage,
            'new_salary'          => $this->newSalary,
            'effective_date'      => $this->effectiveDate,
            'notes'               => $this->notes,
            'updated_by'          => $this->actorId,
        ];
    }

    /**
     * Recompute snapshot against the employee's effective salary for the request's
     * target effective month at approval time so `old_salary` always matches the exact
     * effective salary immediately before applying the increase.
     */
    public function buildApprovalPayload(
        SalaryIncreaseRequest $request,
        Employee $lockedEmployee,
        ?int $approverId = null,
        ?EmployeeSalaryResolverInterface $resolver = null
    ): array {
        $parsedDate = $request->effective_date
            ? Carbon::parse($request->effective_date)
            : now();

        $effectiveSalary = round(
            ($resolver ?? app(EmployeeSalaryResolverInterface::class))
                ->resolveForPeriod($lockedEmployee, $parsedDate->year, $parsedDate->month),
            2
        );

        $recalculated = match ($request->increase_type) {
            SalaryIncreaseRequest::INCREASE_TYPE_PERCENTAGE => self::calculateAmounts(
                $effectiveSalary,
                SalaryIncreaseRequest::INCREASE_TYPE_PERCENTAGE,
                (float) ($request->increase_percentage ?? 0)
            ),
            SalaryIncreaseRequest::INCREASE_TYPE_NEW_SALARY => self::calculateAmounts(
                $effectiveSalary,
                SalaryIncreaseRequest::INCREASE_TYPE_NEW_SALARY,
                (float) $request->new_salary
            ),
            default => self::calculateAmounts(
                $effectiveSalary,
                SalaryIncreaseRequest::INCREASE_TYPE_FIXED,
                (float) $request->increase_amount
            ),
        };

        $actor = $approverId ?? auth()->id();

        return [
            'old_salary'          => $effectiveSalary,
            'increase_amount'     => $recalculated['increase_amount'],
            'increase_percentage' => $recalculated['increase_percentage'],
            'new_salary'          => $recalculated['new_salary'],
            'effective_date'      => $parsedDate->toDateString(),
            'status'              => SalaryIncreaseRequest::STATUS_APPROVED,
            'approved_by'         => $actor,
            'approved_at'         => now(),
            'updated_by'          => $actor,
        ];
    }

    public function buildRejectionPayload(string $reason, ?int $rejectorId = null): array
    {
        $actor = $rejectorId ?? auth()->id();

        return [
            'status'           => SalaryIncreaseRequest::STATUS_REJECTED,
            'rejection_reason' => $reason,
            'rejected_by'      => $actor,
            'rejected_at'      => now(),
            'updated_by'       => $actor,
        ];
    }
}
