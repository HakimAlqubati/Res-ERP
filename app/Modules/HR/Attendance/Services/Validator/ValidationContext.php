<?php

namespace App\Modules\HR\Attendance\Services\Validator;

use App\Models\Employee;
use App\Modules\HR\Attendance\Contracts\AttendanceRepositoryInterface;
use App\Modules\HR\Attendance\Contracts\ShiftResolverInterface;
use App\Modules\HR\Attendance\DTOs\ShiftInfoDTO;
use App\Modules\HR\Attendance\Enums\CheckType;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * سياق التحقق من الحضور
 * 
 * يحتوي على جميع المعلومات اللازمة لتطبيق قواعد التحقق
 */
final readonly class ValidationContext
{
    public function __construct(
        public Employee $employee,
        public Carbon $requestTime,
        public ?ShiftInfoDTO $shiftInfo,
        public string $date,
        // سجلات الشيفت المحددة فقط
        public Collection $shiftRecords,
        // جميع سجلات اليوم (لجميع الورديات)
        public Collection $dailyRecords,
        public mixed $lastRecord,
        public bool $lastIsCheckIn,
        public bool $lastIsCheckOut,
        // هل يوجد سجلات في الشيفت المحددة
        public bool $hasAnyCheckIn,
        public bool $hasAnyCheckOut,
        // هل يوجد أي سجلات في جميع ورديات اليوم
        public bool $hasAnyDailyCheckIn,
        public bool $hasAnyDailyCheckOut,
        // تخطي فحص الوقت المكرر (للإضافة اليدوية)
        public bool $skipDuplicateTimestampCheck = false,
    ) {}

    /**
     * إنشاء سياق التحقق من البيانات
     */
    public static function create(
        Employee $employee,
        Carbon $requestTime,
        ?int $periodId,
        ShiftResolverInterface $shiftResolver,
        AttendanceRepositoryInterface $repository,
        bool $skipDuplicateTimestampCheck = false
    ): self {
        // تحديد الوردية
        $shiftInfo = $shiftResolver->resolve($employee, $requestTime, $repository, $periodId);
        $date = $shiftInfo?->date ?? $requestTime->toDateString();
        $resolvedPeriodId = $shiftInfo?->getPeriodId();

        // جلب السجلات
        $dailyRecords = $repository->getDailyRecords($employee->id, $date);
        $shiftRecords = $resolvedPeriodId
            ? $dailyRecords->where('period_id', $resolvedPeriodId)
            : $dailyRecords;

        // تحليل السجلات
        $lastRecord = $shiftRecords->sortByDesc('id')->first();

        return new self(
            employee: $employee,
            requestTime: $requestTime,
            shiftInfo: $shiftInfo,
            date: $date,
            shiftRecords: $shiftRecords,
            dailyRecords: $dailyRecords,
            lastRecord: $lastRecord,
            lastIsCheckIn: $lastRecord && $lastRecord->check_type === CheckType::CHECKIN->value,
            lastIsCheckOut: $lastRecord && $lastRecord->check_type === CheckType::CHECKOUT->value,
            hasAnyCheckIn: $shiftRecords->where('check_type', CheckType::CHECKIN->value)->isNotEmpty(),
            hasAnyCheckOut: $shiftRecords->where('check_type', CheckType::CHECKOUT->value)->isNotEmpty(),
            hasAnyDailyCheckIn: $dailyRecords->where('check_type', CheckType::CHECKIN->value)->isNotEmpty(),
            hasAnyDailyCheckOut: $dailyRecords->where('check_type', CheckType::CHECKOUT->value)->isNotEmpty(),
            skipDuplicateTimestampCheck: $skipDuplicateTimestampCheck,
        );
    }
}
