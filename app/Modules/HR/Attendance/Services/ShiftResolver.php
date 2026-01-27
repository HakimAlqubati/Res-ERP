<?php

namespace App\Modules\HR\Attendance\Services;

use App\Models\Attendance;
use App\Models\Employee;
use App\Models\WorkPeriod;
use App\Modules\HR\Attendance\Contracts\AttendanceRepositoryInterface;
use App\Modules\HR\Attendance\Contracts\ShiftResolverInterface;
use App\Modules\HR\Attendance\DTOs\ShiftInfoDTO;
use App\Modules\HR\Attendance\Enums\CheckType;
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
     * 
     * @param Employee $employee الموظف
     * @param Carbon $time الوقت المطلوب
     * @param AttendanceRepositoryInterface|null $repository للتحقق من حالة الشيفتات (اختياري)
     * @return ShiftInfoDTO|null
     */
    public function resolve(
        Employee $employee,
        Carbon $time,
        ?AttendanceRepositoryInterface $repository = null,
        ?int $periodId = null
    ): ?ShiftInfoDTO {
        // 1. جلب جميع الورديات المحتملة حول هذا الوقت
        $candidates = $this->getCandidatePeriods($employee, $time);

        if ($candidates->isEmpty()) {
            return null;
        }
        // 2. جمع جميع الشيفتات التي يقع الوقت ضمن نافذتها
        $matchingShifts = $this->getAllMatchingShifts($candidates, $time);

        if ($matchingShifts->isEmpty()) {
            return null;
        }

        // إذا تم تحديد شيفت معين، نبحث عنه
        if ($periodId) {
            $match = $matchingShifts->first(fn($m) => $m['candidate']['period']->id == $periodId);
            return $match ? $this->createShiftDTO($match) : null;
        }

        // 3. إذا كان هناك شيفت واحد فقط، نرجعه مباشرة
        if ($matchingShifts->count() === 1) {
            return $this->createShiftDTO($matchingShifts->first());
        }

        // 4. عند وجود عدة شيفتات متطابقة
        // إذا تم توفير repository، نستخدم الاختيار الذكي
        if ($repository) {
            return $this->selectBestShift($matchingShifts, $employee, $repository, $time);
        }

        // إذا لم يتم توفير repository، نرجع الأول (للتوافق العكسي)
        return $this->createShiftDTO($matchingShifts->first());
    }

    /**
     * جلب جميع الورديات المطابقة للنافذة الزمنية
     * 
     * @param Employee $employee الموظف
     * @param Carbon $time الوقت المطلوب
     * @return Collection قائمة الورديات المطابقة (كل عنصر يحتوي على candidate و bounds)
     */
    public function getMatchingShifts(Employee $employee, Carbon $time): Collection
    {
        $candidates = $this->getCandidatePeriods($employee, $time);

        if ($candidates->isEmpty()) {
            return collect();
        }

        return $this->getAllMatchingShifts($candidates, $time);
    }

    /**
     * جمع جميع الشيفتات التي يقع الوقت ضمن نافذتها
     */
    private function getAllMatchingShifts(Collection $candidates, Carbon $time): Collection
    {
        $matchingShifts = collect();

        foreach ($candidates as $candidate) {
            $bounds = $this->calculateBounds($candidate['period'], $candidate['date']);
            $windowStart = $bounds['windowStart'];
            $windowEnd = $bounds['windowEnd'];

            if ($time->betweenIncluded($windowStart, $windowEnd)) {
                $matchingShifts->push([
                    'candidate' => $candidate,
                    'bounds' => $bounds,
                ]);
            }
        }

        return $matchingShifts;
    }

    /**
     * اختيار أفضل شيفت عند التعارض (الأولوية للشيفتات غير المكتملة)
     */
    private function selectBestShift(
        Collection $matchingShifts,
        Employee $employee,
        AttendanceRepositoryInterface $repository,
        Carbon $requestTime
    ): ?ShiftInfoDTO {

        // تقييم كل شيفت بناءً على حالة الاكتمال
        $scored = $matchingShifts->map(function ($match) use ($employee, $repository, $requestTime) {
            $candidate = $match['candidate'];
            $bounds = $match['bounds'];
            $date = $candidate['date'];
            $periodId = $candidate['period']->id;
            $shiftEnd = $bounds['end'];

            // جلب سجلات هذا الشيفت المحدد
            $records = $repository->getDailyRecords($employee->id, $date)
                ->where('period_id', $periodId);

            // الحصول على آخر سجل
            $lastRecord = $records->sortByDesc('id')->first();
            // تحديد النقاط بناءً على منطق بسيط وذكي
            $score = $this->calculateShiftScore($lastRecord, $shiftEnd, $requestTime);

            return [
                'match' => $match,
                'score' => $score,
                'lastRecord' => $lastRecord,
            ];
        });

        // اختيار الشيفت بأقل نقاط (أعلى أولوية)
        // عند تساوي النقاط، نفضل الشيفت الذي يحتوي على آخر نشاط (ID أكبر)
        // اختيار الشيفت بأقل نقاط (أعلى أولوية)
        // عند تساوي النقاط، نفضل الشيفت الذي يحتوي على آخر نشاط (ID أكبر)
        $best = $scored->sort(fn($a, $b) => $this->compareShiftScores($a, $b))->first();

        return $this->createShiftDTO($best['match']);
    }

    /**
     * دالة المقارنة لترتيب الشيفتات حسب الأفضلية
     */
    private function compareShiftScores(array $a, array $b): int
    {
        if ($a['score'] !== $b['score']) {
            return $a['score'] <=> $b['score'];
        }

        // إذا تساوت النقاط، نأخذ صاح آخر نشاط (الأحدث)
        $idA = $a['lastRecord']?->id ?? 0;
        $idB = $b['lastRecord']?->id ?? 0;

        return $idB <=> $idA; // ترتيب تنازلي حسب الـ ID
    }

    /**
     * حساب النقاط للشيفت بناءً على منطق بسيط
     * 
     * المنطق:
     * - آخر سجل check-in = جاري (0) - الأولوية الأولى
     * - لا توجد سجلات + الوردية لم تنته = جديد (1)
     * - لا توجد سجلات + الوردية انتهت = متأخر جداً (500)
     * - آخر سجل check-out + انتهى وقت الشيفت = مقفل (1000)
     */
    private function calculateShiftScore($lastRecord, Carbon $shiftEnd, Carbon $requestTime): int
    {
        // لا توجد سجلات
        if (!$lastRecord) {
            // التحقق: هل انتهت الوردية؟
            if ($requestTime->gte($shiftEnd)) {
                // الوقت الحالي بعد أو عند نهاية الشيفت = متأخر جداً
                return 500;
            }
            // الوردية لم تنته بعد = جديد
            return 1;
        }

        // آخر سجل هو check-in = جاري (الأولوية الأولى)
        if ($lastRecord->check_type === CheckType::CHECKIN->value) {
            return 0;
        }

        // آخر سجل هو check-out
        // نتحقق: هل انتهى وقت الشيفت؟
        if ($requestTime->gt($shiftEnd)) {
            // الوقت الحالي بعد نهاية الشيفت = مقفل
            return 1000;
        }

        // الوقت ما زال قبل أو عند نهاية الشيفت = يمكن العودة
        return 1;
    }

    /**
     * إنشاء ShiftInfoDTO من بيانات الشيفت
     */
    private function createShiftDTO(array $match): ShiftInfoDTO
    {
        $candidate = $match['candidate'];
        $bounds = $match['bounds'];

        return new ShiftInfoDTO(
            period: $candidate['period'],
            date: $candidate['date'],
            dayName: $candidate['day'],
            start: $bounds['start'],
            end: $bounds['end'],
            windowStart: $bounds['windowStart'],
            windowEnd: $bounds['windowEnd'],
        );
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
