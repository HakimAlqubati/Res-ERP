<?php

namespace App\Modules\HR\Employee\Services;

use App\Models\AppLog;
use App\Models\Employee;
use App\Models\EmployeeServiceTermination;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Exception;

/**
 * Service class for managing Employee Lifecycle events professionally.
 * Handles termination requests, approvals, rejections, and rehiring logic.
 */
class EmployeeLifecycleService
{
    /**
     * Request service termination for an employee.
     *
     * @param Employee $employee
     * @param array $data
     * @return EmployeeServiceTermination
     * @throws Exception
     */
    public function requestTermination(Employee $employee, array $data): EmployeeServiceTermination
    {
        $this->ensureFinancialClearance($employee);

        return DB::transaction(function () use ($employee, $data) {
            $termination = $employee->serviceTermination()->create([
                'termination_date'   => $data['termination_date'],
                'termination_reason' => $data['termination_reason'],
                'notes'              => $data['notes'] ?? null,
                'status'             => EmployeeServiceTermination::STATUS_PENDING,
            ]);

            AppLog::write(
                message: "Termination request created for Employee {$employee->name} (#{$employee->id})",
                level: AppLog::LEVEL_INFO,
                context: 'HR_TERMINATION_REQUEST',
                extra: ['employee_id' => $employee->id, 'termination_id' => $termination->id]
            );

            return $termination;
        });
    }

    /**
     * Approve a pending termination request.
     *
     * @param EmployeeServiceTermination $termination
     * @throws Exception
     */
    public function approveTermination(EmployeeServiceTermination $termination): void
    {
        $this->ensureFinancialClearance($termination->employee);

        DB::beginTransaction();
        try {
            $termination->update([
                'status'      => EmployeeServiceTermination::STATUS_APPROVED,
                'approved_at' => now(),
                'approved_by' => auth()->id(),
            ]);

            // Deactivate Employee
            $employee = $termination->employee;
            if ($employee) {
                $employee->update(['active' => 0]);

                // Deactivate Linked User
                if ($employee->user_id) {
                    $user = User::withTrashed()->find($employee->user_id);
                    if ($user) {
                        $user->update(['active' => 0]);
                    }
                }
            }

            AppLog::write(
                message: "Termination approved for Employee {$employee->name} (#{$employee->id})",
                level: AppLog::LEVEL_INFO,
                context: 'HR_TERMINATION_APPROVED',
                extra: ['employee_id' => $employee?->id, 'termination_id' => $termination->id]
            );

            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Reject a pending termination request.
     *
     * @param EmployeeServiceTermination $termination
     * @param array $data
     * @throws Exception
     */
    public function rejectTermination(EmployeeServiceTermination $termination, array $data): void
    {
        DB::transaction(function () use ($termination, $data) {
            $termination->update([
                'status'           => EmployeeServiceTermination::STATUS_REJECTED,
                'rejected_at'      => now(),
                'rejected_by'      => auth()->id(),
                'rejection_reason' => $data['rejection_reason'] ?? null
            ]);

            AppLog::write(
                message: "Termination request rejected for Employee {$termination->employee->name} (#{$termination->employee->id})",
                level: AppLog::LEVEL_INFO,
                context: 'HR_TERMINATION_REJECTED',
                extra: ['employee_id' => $termination->employee_id, 'termination_id' => $termination->id]
            );
        });
    }

    /**
     * Rehire a terminated employee.
     *
     * @param Employee $employee
     * @param array $data
     * @throws Exception
     */
    public function rehire(Employee $employee, array $data): void
    {
        DB::beginTransaction();
        try {
            // 1. Reactivate employee and update join date
            $employee->update([
                'active' => 1,
                'join_date' => $data['join_date'],
            ]);

            // 2. Reactivate/Restore linked user if exists
            if ($employee->user_id) {
                $user = User::withTrashed()->find($employee->user_id);
                if ($user) {
                    if (method_exists($user, 'trashed') && $user->trashed()) {
                        $user->restore();
                    }
                    $user->update(['active' => 1]);
                }
            }

            // 3. Cancel any pending termination requests
            $employee->serviceTermination()
                ->where('status', EmployeeServiceTermination::STATUS_PENDING)
                ->update([
                    'status' => EmployeeServiceTermination::STATUS_CANCEL,
                    'notes'  => (isset($data['notes']) && $data['notes'] ? $data['notes'] . " (Rehired)" : "Rehired")
                ]);

            // 4. Log the rehire event
            AppLog::write(
                message: "Employee {$employee->name} (#{$employee->id}) rehired successfully with join date: {$data['join_date']}",
                level: AppLog::LEVEL_INFO,
                context: 'HR_REHIRE',
                extra: [
                    'employee_id' => $employee->id,
                    'join_date' => $data['join_date'],
                    'notes' => $data['notes'] ?? null
                ]
            );

            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Guard against terminating an employee with uncleared financial obligations.
     * Throws a ValidationException which is gracefully natively handled by Filament and APIs.
     *
     * @param Employee $employee
     * @throws ValidationException
     */
    protected function ensureFinancialClearance(Employee $employee): void
    {
        // Leverage Eloquent relations rather than raw static calls
        $unpaidBalance = (float) $employee->advancedInstallments()
            ->where('is_paid', false)
            ->sum('installment_amount');

        if ($unpaidBalance > 0) {
            throw ValidationException::withMessages([
                'financial_clearance' => __('Cannot process financial clearance. The employee has outstanding advance installments amounting to: :amount', [
                    'amount' => number_format($unpaidBalance, 2)
                ]),
            ]);
        }
    }
}
