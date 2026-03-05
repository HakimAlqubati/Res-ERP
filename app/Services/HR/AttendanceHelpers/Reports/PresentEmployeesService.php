<?php

namespace App\Services\HR\AttendanceHelpers\Reports;

use App\Models\Attendance;
use App\Models\WorkPeriod;
use App\Modules\HR\Attendance\Services\AttendanceConfig;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * PresentEmployeesService
 *
 * يحدد الموظفين الحاضرين حالياً بناءً على:
 *   1. وجود بصمة دخول (CheckIn) مقبولة في اليوم المطلوب.
 *   2. وقوع الوقت الحالي داخل نافذة الوردية الممتدة:
 *      [start_at - allowedHoursBefore]  ←→  [end_at + allowedHoursAfter]
 */
class PresentEmployeesService
{
    public function __construct(
        protected AttendanceConfig $config
    ) {}

    /**
     * جلب الموظفين الحاضرين في الوقت المحدد (افتراضياً الآن).
     *
     * @param  Carbon|string|null  $datetime  تاريخ ووقت نقطة المراقبة (null = الآن)
     * @param  array               $filters   فلاتر اختيارية: branch_id, department_id
     * @return Collection
     */
    public function getPresentEmployees(Carbon|string|null $datetime = null, array $filters = []): Collection
    {
        $now = $datetime instanceof Carbon
            ? $datetime
            : ($datetime ? Carbon::parse($datetime) : Carbon::now());

        $date        = $now->toDateString();
        $currentTime = $now->format('H:i:s');

        // ── 1. قراءة إعدادات النافذة الزمنية ──────────────────────────────
        $allowedHoursBefore = $this->config->getAllowedHoursBefore();
        $allowedHoursAfter  = $this->config->getAllowedHoursAfter();

        // ── 2. تحديد الورديات النشطة عند الوقت الحالي ─────────────────────
        $activeShiftIds = $this->resolveActiveShiftIds($now, $allowedHoursBefore, $allowedHoursAfter);

        if ($activeShiftIds->isEmpty()) {
            return collect();
        }

        // ── 3. جلب سجلات الحضور المقبولة (CheckIn فقط) ───────────────────
        $query = Attendance::query()
            ->with([
                'employee:id,name,branch_id,department_id',
                'period:id,name,start_at,end_at',
            ])
            ->where('check_type', Attendance::CHECKTYPE_CHECKIN)
            ->where('accepted', 1)
            ->where('check_date', $date)
            ->whereIn('period_id', $activeShiftIds);

        // ── فلتر الفرع ───────────────────────────────────────────────────
        if (!empty($filters['branch_id'])) {
            $query->whereHas(
                'employee',
                fn($q) =>
                $q->where('branch_id', (int) $filters['branch_id'])
            );
        }

        // ── فلتر القسم ──────────────────────────────────────────────────
        if (!empty($filters['department_id'])) {
            $query->whereHas(
                'employee',
                fn($q) =>
                $q->where('department_id', (int) $filters['department_id'])
            );
        }

        // ── 4. التأكد من عدم وجود checkout مقبول لنفس الموظف + وردية + يوم ──
        $query->whereNotExists(function ($sub) use ($date) {
            $sub->from('hr_attendances as checkout_check')
                ->whereColumn('checkout_check.employee_id', 'hr_attendances.employee_id')
                ->whereColumn('checkout_check.period_id',   'hr_attendances.period_id')
                ->where('checkout_check.check_date', $date)
                ->where('checkout_check.check_type', Attendance::CHECKTYPE_CHECKOUT)
                ->where('checkout_check.accepted', 1)
                ->whereNull('checkout_check.deleted_at');
        });

        // ── 5. أول بصمة دخول لكل موظف/وردية لتجنب التكرار ───────────────
        $checkins = $query
            ->orderBy('check_time')
            ->get()
            ->unique(fn($record) => $record->employee_id . '_' . $record->period_id);

        // ── 6. تشكيل الاستجابة ──────────────────────────────────────────────
        return $checkins->values()->map(fn($checkin) => [
            'employee_id'     => $checkin->employee_id,
            'employee_name'   => $checkin->employee?->name,
            'branch_id'       => $checkin->employee?->branch_id,
            'department_id'   => $checkin->employee?->department_id,
            'checkin_time'    => $checkin->check_time,
            'checkin_date'    => $checkin->check_date,
            'attendance_id'   => $checkin->id,
            'period_id'       => $checkin->period_id,
            'period_name'     => $checkin->period?->name,
            'period_start_at' => $checkin->period?->start_at,
            'period_end_at'   => $checkin->period?->end_at,
            'status'          => $checkin->status,
        ]);
    }

    /**
     * جلب الموظفين الغائبين الذين كان يجب أن يكونوا حاضرين الآن.
     *
     * المعيار:
     *   - الموظف مُعيَّن لوردية نشطة حالياً (في hr_employee_periods).
     *   - التعيين ساري في التاريخ المطلوب (start_date <= date <= end_date).
     *   - لم يُسجَّل له أي بصمة دخول مقبولة اليوم لأي وردية نشطة.
     *
     * @param  Collection  $activeShiftIds  الورديات النشطة حالياً
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

        // Employee IDs who already checked in today for any active shift
        $presentEmployeeIds = Attendance::query()
            ->where('check_type', Attendance::CHECKTYPE_CHECKIN)
            ->where('accepted', 1)
            ->where('check_date', $date)
            ->whereIn('period_id', $activeShiftIds)
            ->pluck('employee_id')
            ->unique();

        // Employees assigned to an active shift but with no check-in today
        $query = \App\Models\EmployeePeriod::query()
            ->with(['employee:id,name,branch_id,department_id', 'workPeriod:id,name,start_at,end_at'])
            ->whereIn('period_id', $activeShiftIds)
            ->where(function ($q) use ($date) {
                $q->whereNull('start_date')->orWhere('start_date', '<=', $date);
            })
            ->where(function ($q) use ($date) {
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

        // One record per employee (an employee may be assigned to multiple active shifts)
        $results = $query->get()->unique('employee_id');

        return $results->values()->map(fn($ep) => [
            'employee_id'     => $ep->employee_id,
            'employee_name'   => $ep->employee?->name,
            'branch_id'       => $ep->employee?->branch_id,
            'department_id'   => $ep->employee?->department_id,
            'period_id'       => $ep->period_id,
            'period_name'     => $ep->workPeriod?->name,
            'period_start_at' => $ep->workPeriod?->start_at,
            'period_end_at'   => $ep->workPeriod?->end_at,
        ]);
    }

    /**
     * تقرير شامل: الحاضرون الآن + الغائبون المُقصِّرون.
     *
     * @param  Carbon|string|null  $datetime
     * @param  array               $filters
     * @return array{ present: Collection, expected_absent: Collection, active_shift_ids: Collection }
     */
    public function getReport(Carbon|string|null $datetime = null, array $filters = []): array
    {
        $now = $datetime instanceof Carbon
            ? $datetime
            : ($datetime ? Carbon::parse($datetime) : Carbon::now());

        $date           = $now->toDateString();
        $allowedBefore  = $this->config->getAllowedHoursBefore();
        $allowedAfter   = $this->config->getAllowedHoursAfter();
        $activeShiftIds = $this->resolveActiveShiftIds($now, $allowedBefore, $allowedAfter);

        $present        = $this->getPresentEmployees($now, $filters);
        $expectedAbsent = $this->getExpectedAbsentEmployees($activeShiftIds, $date, $filters);

        return compact('present', 'expectedAbsent', 'activeShiftIds');
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Helpers
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * يحدد الورديات التي يقع الوقت الحالي داخل نافذتها الممتدة.
     *
     * نافذة الوردية:
     *   فتح الباب من  : start_at - $allowedHoursBefore
     *   إغلاق الباب حتى: end_at   + $allowedHoursAfter
     *
     * الورديات الليلية (day_and_night): end_at + 1 يوم
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

                // الورديات الليلية تتجاوز منتصف الليل
                if ($period->day_and_night) {
                    $windowEnd->addDay();
                }

                return $now->between($windowStart, $windowEnd);
            })
            ->pluck('id');
    }
}
