<?php

namespace App\Observers;

use App\Exceptions\HR\PayrollConflictException;
use App\Models\Attendance;
use App\Models\EmployeeOvertime;
use App\Models\Payroll;
use App\Services\HR\Payroll\PayrollLockGuard;
use Carbon\Carbon;
use Illuminate\Validation\ValidationException;

/**
 * Observer for EmployeeOvertime model.
 *
 * Enforces business rules relating to payroll cycles:
 * - Prevents approving overtime if the payroll period is already finalized.
 * - Prevents rolling back approved overtime if a payroll run for that period exists.
 * - Prevents deleting overtime records if a payroll run for that period exists.
 */
class EmployeeOvertimeObserver
{
    public function __construct(
        private readonly PayrollLockGuard $payrollLockGuard,
    ) {}

    /**
     * Prevent creating overtime records if the payroll month is locked,
     * auto-resolve period_id when applicable, and prevent duplicate shift overtime.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function creating(EmployeeOvertime $employeeOvertime): void
    {
        if (empty($employeeOvertime->type)) {
            $employeeOvertime->type = EmployeeOvertime::TYPE_BASED_ON_DAY;
        }

        if ($employeeOvertime->type === EmployeeOvertime::TYPE_BASED_ON_MONTH) {
            $employeeOvertime->period_id = null;
        } elseif (
            empty($employeeOvertime->period_id)
            && ! empty($employeeOvertime->employee_id)
            && ! empty($employeeOvertime->date)
        ) {
            $employeeOvertime->period_id = EmployeeOvertime::resolvePeriodId(
                (int) $employeeOvertime->employee_id,
                (string) $employeeOvertime->date,
                $employeeOvertime->start_time
            );
        }

        if (empty($employeeOvertime->date)) {
            return;
        }

        $date = Carbon::parse($employeeOvertime->date);

        $this->payrollLockGuard->checkLock(
            (int) $employeeOvertime->employee_id,
            $date->year,
            $date->month,
            'date'
        );

        $this->ensureNoDuplicateOvertime($employeeOvertime);
    }


    /**
     * @throws \Exception
     */
    public function saving(EmployeeOvertime $employeeOvertime): void
    {
        if (!$employeeOvertime->exists) {
            return;
        }

        // 1. Prevent redundant status assignments
        // Check if status is being explicitly assigned to its current value
        // Note: Eloquent won't flag 'status' as dirty if assigned the same value, 
        // yet we catch it if someone forces a save with the same state in business logic situations.
        $newStatus = $employeeOvertime->status;
        $oldStatus = $employeeOvertime->getOriginal('status');

        // Check if they are trying to "re-set" the same state
        if ($newStatus === $oldStatus && request()->has('status')) {
            $msg = match ($newStatus) {
                EmployeeOvertime::STATUS_APPROVED => "Overtime already approved.",
                EmployeeOvertime::STATUS_REJECTED => "Overtime already rejected.",
                EmployeeOvertime::STATUS_PENDING  => "Overtime already pending.",
                default => null
            };

            if ($msg) {
                throw new \Exception($msg, 422);
            }
        }
    }

    /**
     * Prevent rolling back (undoing approval) of overtime if payroll has been run.
     * Also prevents updating approved records unless rolling back.
     */
    public function updating(EmployeeOvertime $employeeOvertime): void
    {
        $oldStatus = $employeeOvertime->getOriginal('status');
        $newStatus = $employeeOvertime->status;

        // 2. Protect Approved records from any modifications (hours, reason, etc.)
        if ($oldStatus === EmployeeOvertime::STATUS_APPROVED) {
            // Specific check for Rejecting an Approved record
            if ($newStatus === EmployeeOvertime::STATUS_REJECTED) {
                throw new \Exception("Cannot reject approved overtime.", 422);
            }

            // Only allow transition to PENDING (rollback)
            if ($newStatus !== EmployeeOvertime::STATUS_PENDING) {
                throw new \Exception("Overtime already approved.", 422);
            }
        }

        // 3. Block approval if the payroll period is already finalized for this employee.
        $isTransitioningToApproved = $employeeOvertime->isDirty('status') &&
            $newStatus === EmployeeOvertime::STATUS_APPROVED &&
            $oldStatus !== EmployeeOvertime::STATUS_APPROVED;

        $isDateChanging = $employeeOvertime->isDirty('date');

        if (($isTransitioningToApproved || $isDateChanging) && !empty($employeeOvertime->date)) {
            $date = Carbon::parse($employeeOvertime->date);

            $this->payrollLockGuard->checkLock(
                (int) $employeeOvertime->employee_id,
                $date->year,
                $date->month,
                'date'
            );
        }

        // 4. Detect rollback: status field transitioning from approved to something else.
        if (
            $employeeOvertime->isDirty('status') &&
            $newStatus !== EmployeeOvertime::STATUS_APPROVED &&
            $oldStatus === EmployeeOvertime::STATUS_APPROVED
        ) {
            $this->ensureNoConflictWithPayroll($employeeOvertime);
        }

        if ($employeeOvertime->type === EmployeeOvertime::TYPE_BASED_ON_MONTH) {
            $employeeOvertime->period_id = null;
        }

        if ($employeeOvertime->isDirty(['employee_id', 'date', 'type', 'period_id'])) {
            $this->ensureNoDuplicateOvertime($employeeOvertime);
        }
    }

    /**
     * Prevent deletion of overtime records if payroll has been run.
     * 
     * @param EmployeeOvertime $employeeOvertime
     * @return void
     * @throws PayrollConflictException
     */
    public function deleting(EmployeeOvertime $employeeOvertime): void
    {
        if (!empty($employeeOvertime->date)) {
            $date = Carbon::parse($employeeOvertime->date);

            $this->payrollLockGuard->checkLock(
                (int) $employeeOvertime->employee_id,
                $date->year,
                $date->month,
                'date'
            );
        }

        $this->ensureNoConflictWithPayroll($employeeOvertime);
    }

    /**
     * Core validation logic to find existing PayrollRuns for the overtime period.
     * 
     * @param EmployeeOvertime $employeeOvertime
     * @return void
     * @throws PayrollConflictException
     */
    private function ensureNoConflictWithPayroll(EmployeeOvertime $employeeOvertime): void
    {
        if (empty($employeeOvertime->date)) {
            return;
        }

        $date = Carbon::parse($employeeOvertime->date);

        $conflictExists = Payroll::query()
            ->where('year', $date->year)
            ->where('month', $date->month)
            ->where('employee_id', $employeeOvertime->employee_id)
            ->exists();

        if ($conflictExists) {
            throw PayrollConflictException::overtimeLockedByPayroll();
        }
    }

    /**
     * Prevent duplicate active overtime entries for the same employee, date, type, and shift (period_id).
     *
     * Rules:
     * - TYPE_BASED_ON_MONTH (full-day overtime): at most 1 active record per employee per date.
     * - TYPE_BASED_ON_DAY (hourly/shift overtime): at most 1 active record per employee, date, and period_id.
     *   If period_id is NULL and employee has no NO_SHIFT attendance on that date, it acts as a general
     *   whole-day manual entry and conflicts with any based_on_day entry on that date.
     *
     * @throws ValidationException
     */
    private function ensureNoDuplicateOvertime(EmployeeOvertime $employeeOvertime): void
    {
        if (empty($employeeOvertime->employee_id) || empty($employeeOvertime->date)) {
            return;
        }

        $dateStr = Carbon::parse($employeeOvertime->date)->toDateString();
        $type = $employeeOvertime->type ?: EmployeeOvertime::TYPE_BASED_ON_DAY;

        $baseQuery = EmployeeOvertime::query()
            ->where('employee_id', $employeeOvertime->employee_id)
            ->whereDate('date', $dateStr)
            ->where('type', $type);

        if ($employeeOvertime->exists && $employeeOvertime->id) {
            $baseQuery->where('id', '!=', $employeeOvertime->id);
        }

        if ($type === EmployeeOvertime::TYPE_BASED_ON_MONTH) {
            if ($baseQuery->exists()) {
                throw ValidationException::withMessages([
                    'date' => "Overtime record already exists for employee #{$employeeOvertime->employee_id} on {$dateStr}.",
                ]);
            }
            return;
        }

        $noShiftValue = \App\Modules\HR\Attendance\Enums\AttendanceStatus::NO_SHIFT->value;
        $hasNoShiftAttendance = Attendance::query()
            ->where('employee_id', $employeeOvertime->employee_id)
            ->where('check_date', $dateStr)
            ->where('accepted', 1)
            ->whereNull('deleted_at')
            ->where(function ($q) use ($noShiftValue) {
                $q->whereNull('period_id')
                    ->orWhere('status', $noShiftValue);
            })
            ->exists();

        if ($employeeOvertime->period_id !== null) {
            $periodId = (int) $employeeOvertime->period_id;

            $conflict = $baseQuery->where(function ($q) use ($periodId, $hasNoShiftAttendance) {
                $q->where('period_id', $periodId);
                if (! $hasNoShiftAttendance) {
                    $q->orWhereNull('period_id');
                }
            })->exists();
        } else {
            if ($hasNoShiftAttendance) {
                $conflict = $baseQuery->whereNull('period_id')->exists();
            } else {
                $conflict = $baseQuery->exists();
            }
        }

        if ($conflict) {
            throw ValidationException::withMessages([
                'date' => "Overtime record already exists for employee #{$employeeOvertime->employee_id} on {$dateStr} for this shift.",
            ]);
        }
    }

    /**
     * Prevent restoring a soft-deleted overtime record if the payroll period is locked
     * or if an active duplicate record already exists for the same shift.
     *
     * @throws ValidationException
     */
    public function restoring(EmployeeOvertime $employeeOvertime): void
    {
        if (! empty($employeeOvertime->date)) {
            $date = Carbon::parse($employeeOvertime->date);

            $this->payrollLockGuard->checkLock(
                (int) $employeeOvertime->employee_id,
                $date->year,
                $date->month,
                'date'
            );
        }

        $this->ensureNoDuplicateOvertime($employeeOvertime);
    }

    /**
     * Handle the EmployeeOvertime "created" event.
     */
    public function created(EmployeeOvertime $employeeOvertime): void
    {
        //
    }

    /**
     * Handle the EmployeeOvertime "updated" event.
     */
    public function updated(EmployeeOvertime $employeeOvertime): void
    {
        //
    }

    /**
     * Handle the EmployeeOvertime "saved" event.
     */
    public function saved(EmployeeOvertime $employeeOvertime): void
    {
        clearEmployeeDailyAttendanceCache($employeeOvertime->employee_id, $employeeOvertime->date);
    }

    /**
     * Handle the EmployeeOvertime "deleted" event.
     */
    public function deleted(EmployeeOvertime $employeeOvertime): void
    {
        clearEmployeeDailyAttendanceCache($employeeOvertime->employee_id, $employeeOvertime->date);
    }

    /**
     * Handle the EmployeeOvertime "restored" event.
     */
    public function restored(EmployeeOvertime $employeeOvertime): void
    {
        clearEmployeeDailyAttendanceCache($employeeOvertime->employee_id, $employeeOvertime->date);
    }

    /**
     * Handle the EmployeeOvertime "force deleted" event.
     */
    public function forceDeleted(EmployeeOvertime $employeeOvertime): void
    {
        //
    }
}
