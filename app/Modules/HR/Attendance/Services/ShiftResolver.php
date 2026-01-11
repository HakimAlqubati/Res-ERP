<?php

namespace App\Modules\HR\Attendance\Services;

use App\Models\Employee;
use App\Models\WorkPeriod;
use App\Modules\HR\Attendance\Contracts\ShiftResolverInterface;
use App\Modules\HR\Attendance\DTOs\ShiftInfoDTO;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * خدمة تحديد الوردية المناسبة
 * 
 * تحدد الوردية المناسبة للموظف بناءً على الوقت المطلوب
 * مع مراعاة الورديات الليلية والسماحيات
 */
class ShiftResolver implements ShiftResolverInterface
{
    public function __construct(
        private AttendanceConfig $config
    ) {}

    /**
     * تحديد الوردية المناسبة للموظف في وقت معين
     */
    public function resolve(Employee $employee, Carbon $time): ?ShiftInfoDTO
    {
        // 1. جلب جميع الورديات المحتملة حول هذا الوقت
        $candidates = $this->getCandidatePeriods($employee, $time);

        if ($candidates->isEmpty()) {
            return null;
        }

        // 2. البحث عن الوردية التي يقع الوقت ضمن نافذتها
        foreach ($candidates as $candidate) {
            $bounds = $this->calculateBounds($candidate['period'], $candidate['date']);
            $windowStart = $bounds['windowStart'];
            $windowEnd = $bounds['windowEnd'];

            if ($time->betweenIncluded($windowStart, $windowEnd)) {
                return new ShiftInfoDTO(
                    period: $candidate['period'],
                    date: $candidate['date'],
                    dayName: $candidate['day'],
                    start: $bounds['start'],
                    end: $bounds['end'],
                    windowStart: $windowStart,
                    windowEnd: $windowEnd,
                );
            }
        }

        return null;
    }

    /**
     * جلب الورديات المحتملة للموظف حول تاريخ معين
     */
    private function getCandidatePeriods(Employee $employee, Carbon $refTime): Collection
    {
        // نبحث في ثلاثة أيام: أمس، اليوم، غداً
        // لتغطية الورديات الليلية
        $dates = [
            $refTime->copy()->subDay()->toDateString(),
            $refTime->toDateString(),
            $refTime->copy()->addDay()->toDateString(),
        ];

        $candidates = collect();

        // تحميل الورديات مسبقاً لتجنب N+1
        $employee->loadMissing('employeePeriods.workPeriod', 'employeePeriods.days');

        foreach ($dates as $date) {
            $dayName = strtolower(Carbon::parse($date)->format('D'));

            foreach ($employee->employeePeriods as $ep) {
                // التحقق من نطاق التاريخ
                if (!$this->isWithinDateRange($ep, $date)) {
                    continue;
                }

                // التحقق من يوم الأسبوع
                if (!$this->isWorkingDay($ep, $dayName)) {
                    continue;
                }

                if ($ep->workPeriod) {
                    $candidates->push([
                        'period' => $ep->workPeriod,
                        'date' => $date,
                        'day' => $dayName,
                    ]);
                }
            }
        }

        return $candidates;
    }

    /**
     * التحقق من أن التاريخ ضمن نطاق الوردية
     */
    private function isWithinDateRange($employeePeriod, string $date): bool
    {
        if ($employeePeriod->start_date > $date) {
            return false;
        }

        if ($employeePeriod->end_date && $employeePeriod->end_date < $date) {
            return false;
        }

        return true;
    }

    /**
     * التحقق من أن اليوم هو يوم عمل
     */
    private function isWorkingDay($employeePeriod, string $dayName): bool
    {
        return $employeePeriod->days->contains('day_of_week', $dayName);
    }

    /**
     * حساب حدود الوردية
     */
    public function calculateBounds(WorkPeriod $period, string $shiftDate): array
    {
        $startStr = $period->start_at;
        $endStr = $period->end_at;

        $shiftStart = Carbon::parse("$shiftDate $startStr");
        $shiftEnd = Carbon::parse("$shiftDate $endStr");

        // معالجة الوردية الليلية
        if ($period->day_and_night || $shiftEnd->lt($shiftStart)) {
            if ($shiftEnd->lt($shiftStart)) {
                $shiftEnd->addDay();
            }
        }

        $allowedBefore = $this->config->getAllowedHoursBefore();
        $allowedAfter = $this->config->getAllowedHoursAfter();

        return [
            'start' => $shiftStart,
            'end' => $shiftEnd,
            'windowStart' => $shiftStart->copy()->subHours($allowedBefore),
            'windowEnd' => $shiftEnd->copy()->addHours($allowedAfter),
        ];
    }
}
