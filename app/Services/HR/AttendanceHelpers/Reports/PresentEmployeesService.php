<?php

namespace App\Services\HR\AttendanceHelpers\Reports;

use App\Models\Attendance;
use App\Models\Employee;
use App\Models\EmployeePeriod;
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
 *   1. An accepted check-in record exists for the requested day (or previous day for overnight shifts).
 *   2. The observed time falls within the extended shift window:
 *      [start_at - allowedHoursBefore]  ←→  [end_at + allowedHoursAfter]
 *
 * Overnight shifts (day_and_night = true) that started yesterday and whose
 * window still covers the current time are handled correctly by also checking
 * the previous day's window and searching check-ins on that date.
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

        // ── 1. Read shift window configuration ─────────────────────────────
        $allowedHoursBefore = $this->config->getAllowedHoursBefore();
        $allowedHoursAfter  = $this->config->getAllowedHoursAfter();

        // ── 2. Identify active shift windows (period_id + correct check_date) ─
        $windows = $this->resolveActiveShiftWindows($now, $allowedHoursBefore, $allowedHoursAfter);

        if ($windows->isEmpty()) {
            return collect();
        }

        // ── 3. Fetch accepted check-in records — grouped by check_date ─────
        //       Overnight shifts may have check_date = yesterday.
        $grouped = $windows->groupBy('check_date');

        $query = Attendance::query()
            ->with([
                'employee:id,name,branch_id,department_id',
                'period:id,name,start_at,end_at',
            ])
            ->where('check_type', Attendance::CHECKTYPE_CHECKIN)
            ->where('accepted', 1)
            ->where(function ($q) use ($grouped) {
                foreach ($grouped as $checkDate => $shiftsOnDate) {
                    $periodIds = $shiftsOnDate->pluck('period_id');
                    $q->orWhere(function ($inner) use ($checkDate, $periodIds) {
                        $inner->where('check_date', $checkDate)
                            ->whereIn('period_id', $periodIds);
                    });
                }
            });

        // ── Branch filter ───────────────────────────────────────────────────
        if (!empty($filters['branch_id'])) {
            $query->whereHas(
                'employee',
                fn($q) => $q->where('branch_id', (int) $filters['branch_id'])
            );
        }

        // ── Department filter ───────────────────────────────────────────────
        if (!empty($filters['department_id'])) {
            $query->whereHas(
                'employee',
                fn($q) => $q->where('department_id', (int) $filters['department_id'])
            );
        }

        // ── 4. Exclude employees who already have an accepted checkout ──────
        //       Use whereColumn on check_date so overnight shifts match correctly.
        $query->whereNotExists(function ($sub) {
            $sub->from('hr_attendances as checkout_check')
                ->whereColumn('checkout_check.employee_id', 'hr_attendances.employee_id')
                ->whereColumn('checkout_check.period_id',   'hr_attendances.period_id')
                ->whereColumn('checkout_check.check_date',  'hr_attendances.check_date') // ← dynamic, not fixed date
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
     * Accepts the full windows collection so that overnight shifts whose
     * check_date is yesterday are correctly excluded from the absent list
     * when the employee already has a valid check-in on that date.
     *
     * @param  Collection  $activeShiftWindows  [{period_id, check_date}, ...]
     * @param  array       $filters             branch_id, department_id
     * @return Collection
     */
    public function getExpectedAbsentEmployees(
        Collection $activeShiftWindows,
        array $filters = []
    ): Collection {
        if ($activeShiftWindows->isEmpty()) {
            return collect();
        }

        $allPeriodIds = $activeShiftWindows->pluck('period_id')->unique()->values();
        $allDates     = $activeShiftWindows->pluck('check_date')->unique()->values()->toArray();

        // ── Collect IDs of employees who already have an accepted check-in ──
        $groupedWindows = $activeShiftWindows->groupBy('check_date');
        $presentEmployeeIds = Attendance::query()
            ->where('check_type', Attendance::CHECKTYPE_CHECKIN)
            ->where('accepted', 1)
            ->where(function ($q) use ($groupedWindows) {
                foreach ($groupedWindows as $checkDate => $shiftsOnDate) {
                    $periodIds = $shiftsOnDate->pluck('period_id');
                    $q->orWhere(function ($inner) use ($checkDate, $periodIds) {
                        $inner->where('check_date', $checkDate)
                            ->whereIn('period_id', $periodIds);
                    });
                }
            })
            ->pluck('employee_id')
            ->unique();

        $earliestDate = min($allDates);
        $latestDate   = max($allDates);

        // ── Employees assigned to an active shift but missing a check-in ────
        $query = EmployeePeriod::query()
            ->with(['employee:id,name,branch_id,department_id', 'workPeriod:id,name,start_at,end_at'])
            ->whereIn('period_id', $allPeriodIds)
            ->where(function ($q) use ($latestDate) {
                $q->whereNull('start_date')->orWhere('start_date', '<=', $latestDate);
            })
            ->where(function ($q) use ($earliestDate) {
                $q->whereNull('end_date')->orWhere('end_date', '>=', $earliestDate);
            })
            ->whereNotIn('employee_id', $presentEmployeeIds)
            ->whereHas('employee');

        // تطبيق الفلاتر (الفرع والقسم)
        if (!empty($filters['branch_id'])) {
            $query->whereHas('employee', fn($q) => $q->where('branch_id', (int) $filters['branch_id']));
        }
        if (!empty($filters['department_id'])) {
            $query->whereHas('employee', fn($q) => $q->where('department_id', (int) $filters['department_id']));
        }

        // جلب النتائج، ثم فلترتها لضمان أن الوقت الحالي داخل أوقات الشفت الفعلية
        $now = Carbon::now(); // أو نمرره كمعامل للدالة إذا أردت استخدام $datetime

        $results = $query->get()->filter(function ($ep) use ($now, $activeShiftWindows) {
            $window = $activeShiftWindows->firstWhere('period_id', $ep->period_id);
            if (!$window) return false;

            $checkDate = $window['check_date'];
            $shiftStartTime = Carbon::parse("{$checkDate} {$ep->workPeriod->start_at}");
            $shiftEndTime   = Carbon::parse("{$checkDate} {$ep->workPeriod->end_at}");

            // معالجة الشفتات الليلية
            if ($ep->workPeriod->day_and_night) {
                $shiftEndTime->addDay();
            }

            // الموظف يعتبر متوقع حضوره وغائب "الآن" فقط إذا كنا فعلياً داخل وقت الشفت!
            // يمكنك استخدام greaterThanOrEqualTo إذا أردت إبقاءه غائباً حتى بعد انتهاء الشفت بقليل
            return $now->between($shiftStartTime, $shiftEndTime);
        })->unique('employee_id');

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

        $allowedBefore  = $this->config->getAllowedHoursBefore();
        $allowedAfter   = $this->config->getAllowedHoursAfter();

        // Compute windows once and reuse for both present and expectedAbsent
        $windows = $this->resolveActiveShiftWindows($now, $allowedBefore, $allowedAfter);

        $hasBranchFilter = !empty($filters['branch_id']);

        return new PresentReportDTO(
            present: $this->getPresentEmployees($now, $filters),
            expectedAbsent: $this->getExpectedAbsentEmployees($windows, $filters),
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
     * Returns active shift windows covering the given time.
     *
     * Each element in the returned collection is an array:
     *   ['period_id' => int, 'check_date' => 'Y-m-d']
     *
     * For a regular (same-day) shift:
     *   check_date = today
     *
     * For an overnight shift (day_and_night = true) whose window started
     * YESTERDAY and is still covering $now (e.g. now = 00:30, shift ends 03:00):
     *   check_date = yesterday   ← employees checked in on the previous date
     *
     * This is the single source of truth used by both getPresentEmployees()
     * and getExpectedAbsentEmployees().
     */
    protected function resolveActiveShiftWindows(
        Carbon $now,
        int $allowedHoursBefore,
        int $allowedHoursAfter
    ): Collection {
        $today     = $now->toDateString();
        $yesterday = $now->copy()->subDay()->toDateString();
        $windows   = collect();

        WorkPeriod::query()
            ->where('active', 1)
            ->get()
            ->each(function (WorkPeriod $period) use (
                $now,
                $today,
                $yesterday,
                $allowedHoursBefore,
                $allowedHoursAfter,
                $windows
            ) {
                // ── Check today's window first ──────────────────────────────
                $startToday = Carbon::parse("{$today} {$period->start_at}")->subHours($allowedHoursBefore);
                $endToday   = Carbon::parse("{$today} {$period->end_at}")->addHours($allowedHoursAfter);

                if ($period->day_and_night) {
                    $endToday->addDay();
                }

                if ($now->between($startToday, $endToday)) {
                    $windows->push(['period_id' => $period->id, 'check_date' => $today]);
                    return; // Today's window matches; no need to check yesterday
                }

                // ── For overnight shifts, also check yesterday's window ──────
                // Example: shift 15:00 → 03:00 (next day).
                // At 00:30 on 2026-03-17:
                //   yesterdayStart = 2026-03-16 15:00 - buffer
                //   yesterdayEnd   = 2026-03-16 03:00 + 1day + buffer = 2026-03-17 03:00+
                //   → 00:30 is within window → check_date = 2026-03-16
                if ($period->day_and_night) {
                    $startYesterday = Carbon::parse("{$yesterday} {$period->start_at}")->subHours($allowedHoursBefore);
                    $endYesterday   = Carbon::parse("{$yesterday} {$period->end_at}")->addHours($allowedHoursAfter)->addDay();

                    if ($now->between($startYesterday, $endYesterday)) {
                        $windows->push(['period_id' => $period->id, 'check_date' => $yesterday]);
                    }
                }
            });

        return $windows;
    }

    /**
     * Backward-compatible helper — returns unique active period IDs only.
     * Delegates to resolveActiveShiftWindows internally.
     */
    protected function resolveActiveShiftIds(
        Carbon $now,
        int $allowedHoursBefore,
        int $allowedHoursAfter
    ): Collection {
        return $this->resolveActiveShiftWindows($now, $allowedHoursBefore, $allowedHoursAfter)
            ->pluck('period_id')
            ->unique()
            ->values();
    }
}
