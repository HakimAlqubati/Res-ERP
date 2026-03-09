<?php

namespace App\Services\HR\AttendanceHelpers\Reports;

use App\Models\Attendance;
use App\Models\Employee;
use App\Models\WorkPeriod;
use App\Modules\HR\Attendance\Services\AttendanceConfig;
use App\Services\HR\AttendanceHelpers\Reports\DTOs\ExpectedAbsentEmployeeDTO;
use App\Services\HR\AttendanceHelpers\Reports\DTOs\PresentEmployeeDTO;
use App\Services\HR\AttendanceHelpers\Reports\DTOs\PresentReportDTO;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * PresentEmployeesService
 *
 * Determines which employees are currently present based on:
 *   1. An accepted check-in record exists for the requested day.
 *   2. The observed time falls within the extended shift window:
 *      [start_at - allowedHoursBefore]  ←→  [end_at + allowedHoursAfter]
 */
class PresentEmployeesService
{
    public function __construct(
        protected AttendanceConfig $config
    ) {}

    /**
     * Retrieve employees who are present at the given datetime (defaults to now).
     *
     * @param  Carbon|string|null  $datetime  Observation point (null = now)
     * @param  array               $filters   Optional filters: branch_id, department_id
     * @return Collection
     */
    public function getPresentEmployees(Carbon|string|null $datetime = null, array $filters = []): Collection
    {
        $now = $datetime instanceof Carbon
            ? $datetime
            : ($datetime ? Carbon::parse($datetime) : Carbon::now());

        $date = $now->toDateString();

        // ── 1. Read shift window configuration ─────────────────────────────
        $allowedHoursBefore = $this->config->getAllowedHoursBefore();
        $allowedHoursAfter  = $this->config->getAllowedHoursAfter();

        // ── 2. Identify shifts whose window covers the observed time ────────
        $activeShiftIds = $this->resolveActiveShiftIds($now, $allowedHoursBefore, $allowedHoursAfter);

        if ($activeShiftIds->isEmpty()) {
            return collect();
        }

        // ── 3. Fetch accepted check-in records for active shifts ────────────
        $query = Attendance::query()
            ->with([
                'employee:id,name,branch_id,department_id',
                'period:id,name,start_at,end_at',
            ])
            ->where('check_type', Attendance::CHECKTYPE_CHECKIN)
            ->where('accepted', 1)
            ->where('check_date', $date)
            ->whereIn('period_id', $activeShiftIds);

        // ── Branch filter ───────────────────────────────────────────────────
        if (!empty($filters['branch_id'])) {
            $query->whereHas(
                'employee',
                fn($q) =>
                $q->where('branch_id', (int) $filters['branch_id'])
            );
        }

        // ── Department filter ───────────────────────────────────────────────
        if (!empty($filters['department_id'])) {
            $query->whereHas(
                'employee',
                fn($q) =>
                $q->where('department_id', (int) $filters['department_id'])
            );
        }

        // ── 4. Exclude employees who already have an accepted checkout ──────
        $query->whereNotExists(function ($sub) use ($date) {
            $sub->from('hr_attendances as checkout_check')
                ->whereColumn('checkout_check.employee_id', 'hr_attendances.employee_id')
                ->whereColumn('checkout_check.period_id',   'hr_attendances.period_id')
                ->where('checkout_check.check_date', $date)
                ->where('checkout_check.check_type', Attendance::CHECKTYPE_CHECKOUT)
                ->where('checkout_check.accepted', 1)
                ->whereNull('checkout_check.deleted_at');
        });

        // ── 5. Keep only the earliest check-in per (employee, shift) ────────
        $checkins = $query
            ->orderBy('check_time')
            ->get()
            ->unique(fn($record) => $record->employee_id . '_' . $record->period_id);

        // ── 6. Map to DTOs ──────────────────────────────────────────────────
        return $checkins->values()->map(
            fn($checkin) => PresentEmployeeDTO::fromAttendance($checkin)
        );
    }

    /**
     * Retrieve employees who should be present now but have not checked in yet.
     *
     * Criteria:
     *   - The employee is assigned to a currently-active shift (hr_employee_periods).
     *   - The assignment is valid on the requested date (start_date <= date <= end_date).
     *   - No accepted check-in exists for them today on any active shift.
     *
     * @param  Collection  $activeShiftIds  IDs of shifts whose window covers now
     * @param  string      $date            Y-m-d
     * @param  array       $filters         branch_id, department_id
     * @return Collection
     */
    public function getExpectedAbsentEmployees(
        Collection $activeShiftIds,
        string $date,
        array $filters = []
    ): Collection {
        if ($activeShiftIds->isEmpty()) {
            return collect();
        }

        // Collect IDs of employees who already have an accepted check-in today
        $presentEmployeeIds = Attendance::query()
            ->where('check_type', Attendance::CHECKTYPE_CHECKIN)
            ->where('accepted', 1)
            ->where('check_date', $date)
            ->whereIn('period_id', $activeShiftIds)
            ->pluck('employee_id')
            ->unique();

        // Employees assigned to an active shift but missing a check-in today
        $query = \App\Models\EmployeePeriod::query()
            ->with(['employee:id,name,branch_id,department_id', 'workPeriod:id,name,start_at,end_at'])
            ->whereIn('period_id', $activeShiftIds)
            ->where(function ($q) use ($date) {
                // Assignment starts on or before the requested date
                $q->whereNull('start_date')->orWhere('start_date', '<=', $date);
            })
            ->where(function ($q) use ($date) {
                // Assignment ends on or after the requested date (or has no end)
                $q->whereNull('end_date')->orWhere('end_date', '>=', $date);
            })
            ->whereNotIn('employee_id', $presentEmployeeIds)
            ->whereHas('employee');

        if (!empty($filters['branch_id'])) {
            $query->whereHas(
                'employee',
                fn($q) =>
                $q->where('branch_id', (int) $filters['branch_id'])
            );
        }

        if (!empty($filters['department_id'])) {
            $query->whereHas(
                'employee',
                fn($q) =>
                $q->where('department_id', (int) $filters['department_id'])
            );
        }

        // One entry per employee (they may be assigned to multiple active shifts)
        $results = $query->get()->unique('employee_id');

        return $results->values()->map(
            fn($ep) => ExpectedAbsentEmployeeDTO::fromEmployeePeriod($ep)
        );
    }

    /**
     * Full attendance snapshot — returns a PresentReportDTO that owns the response shape.
     *
     * @param  Carbon|string|null  $datetime  Observation point (null = now)
     * @param  array               $filters   branch_id, department_id
     */
    public function getReport(Carbon|string|null $datetime = null, array $filters = []): PresentReportDTO
    {
        $now = $datetime instanceof Carbon
            ? $datetime
            : ($datetime ? Carbon::parse($datetime) : Carbon::now());

        $date           = $now->toDateString();
        $allowedBefore  = $this->config->getAllowedHoursBefore();
        $allowedAfter   = $this->config->getAllowedHoursAfter();
        $activeShiftIds = $this->resolveActiveShiftIds($now, $allowedBefore, $allowedAfter);

        $hasBranchFilter = !empty($filters['branch_id']);

        return new PresentReportDTO(
            present: $this->getPresentEmployees($now, $filters),
            expectedAbsent: $this->getExpectedAbsentEmployees($activeShiftIds, $date, $filters),
            totalEmployees: $this->countTotalEmployees($filters),
            datetime: $now,
            hasBranchFilter: $hasBranchFilter,
            totalEmployeesByBranch: $this->countTotalEmployeesByBranch($filters)
        );
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /**
     * Count total active employees matching the given filters.
     * Used to show "X present out of Y total" in the response.
     */
    protected function countTotalEmployees(array $filters = []): int
    {
        $query = Employee::query()->active();

        if (!empty($filters['branch_id'])) {
            $query->where('branch_id', (int) $filters['branch_id']);
        }

        if (!empty($filters['department_id'])) {
            $query->where('department_id', (int) $filters['department_id']);
        }

        return $query->count();
    }

    /**
     * Get the count of total active employees grouped by branch matching the given filters.
     */
    protected function countTotalEmployeesByBranch(array $filters = []): Collection
    {
        $query = Employee::query()->active();

        if (!empty($filters['branch_id'])) {
            $query->where('branch_id', (int) $filters['branch_id']);
        }

        if (!empty($filters['department_id'])) {
            $query->where('department_id', (int) $filters['department_id']);
        }

        return $query->groupBy('branch_id')
            ->selectRaw('branch_id, count(*) as total')
            ->pluck('total', 'branch_id');
    }

    /**
     * Returns IDs of all active shifts whose extended window covers the given time.
     *
     * Extended window per shift:
     *   Opens  : start_at - $allowedHoursBefore
     *   Closes : end_at   + $allowedHoursAfter
     *
     * Overnight shifts (day_and_night = true): end_at is pushed to the next day.
     */
    protected function resolveActiveShiftIds(
        Carbon $now,
        int $allowedHoursBefore,
        int $allowedHoursAfter
    ): Collection {
        $today = $now->toDateString();

        return WorkPeriod::query()
            ->where('active', 1)
            ->get()
            ->filter(function (WorkPeriod $period) use ($now, $today, $allowedHoursBefore, $allowedHoursAfter) {
                $windowStart = Carbon::parse("{$today} {$period->start_at}")
                    ->subHours($allowedHoursBefore);

                $windowEnd = Carbon::parse("{$today} {$period->end_at}")
                    ->addHours($allowedHoursAfter);

                // Overnight shifts cross midnight — push the end to the next day
                if ($period->day_and_night) {
                    $windowEnd->addDay();
                }

                return $now->between($windowStart, $windowEnd);
            })
            ->pluck('id');
    }
}
