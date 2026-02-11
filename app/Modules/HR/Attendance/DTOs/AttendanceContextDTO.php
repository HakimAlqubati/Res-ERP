<?php

namespace App\Modules\HR\Attendance\DTOs;

use App\Models\Attendance;
use App\Models\Employee;
use App\Models\WorkPeriod;
use App\Modules\HR\Attendance\Enums\AttendanceStatus;
use App\Modules\HR\Attendance\Enums\AttendanceType;
use App\Modules\HR\Attendance\Enums\CheckType;
use Carbon\Carbon;

/**
 * DTO لسياق عملية الحضور
 * 
 * يحتوي على جميع البيانات المطلوبة لمعالجة تسجيل الحضور
 */
class AttendanceContextDTO
{
    public function __construct(
        // البيانات الأساسية
        public readonly Employee $employee,
        public readonly Carbon $requestTime,
        public readonly array $payload,
        public readonly AttendanceType $attendanceType,

        // مرجع المصدر (Polymorphic)
        public readonly ?string $sourceType = null,
        public readonly ?int $sourceId = null,

        // بيانات الوردية (تُحدد لاحقاً)
        public ?WorkPeriod $workPeriod = null,
        public ?string $shiftDate = null,
        public ?string $shiftDayName = null,
        public ?ShiftInfoDTO $shiftInfo = null,

        // الحالة السابقة
        public ?Attendance $lastCheckIn = null,

        // نتائج المعالجة
        public ?CheckType $checkType = null,
        public ?AttendanceStatus $status = null,
        public int $delayMinutes = 0,
        public int $earlyArrivalMinutes = 0,
        public int $lateDepartureMinutes = 0,
        public int $earlyDepartureMinutes = 0,
        public int $actualMinutes = 0,
    ) {}

    /**
     * إنشاء من payload الطلب
     */
    public static function fromPayload(Employee $employee, array $payload): self
    {
        $requestTime = isset($payload['date_time'])
            ? Carbon::parse($payload['date_time'])
            : Carbon::now();

        $attendanceType = AttendanceType::tryFrom($payload['attendance_type'] ?? 'rfid')
            ?? AttendanceType::RFID;

        return new self(
            employee: $employee,
            requestTime: $requestTime,
            payload: $payload,
            attendanceType: $attendanceType,
            sourceType: $payload['source_type'] ?? null,
            sourceId: isset($payload['source_id']) ? (int) $payload['source_id'] : null,
        );
    }

    /**
     * تعيين معلومات الوردية
     */
    public function setShiftInfo(ShiftInfoDTO $shiftInfo): self
    {
        $this->workPeriod = $shiftInfo->period;
        $this->shiftDate = $shiftInfo->date;
        $this->shiftDayName = $shiftInfo->dayName;
        $this->shiftInfo = $shiftInfo;

        return $this;
    }

    /**
     * تعيين نوع العملية
     */
    public function setCheckType(CheckType $checkType): self
    {
        $this->checkType = $checkType;
        return $this;
    }

    /**
     * تعيين الحالة
     */
    public function setStatus(AttendanceStatus $status): self
    {
        $this->status = $status;
        return $this;
    }

    /**
     * تعيين سجل الدخول السابق
     */
    public function setLastCheckIn(?Attendance $checkIn): self
    {
        $this->lastCheckIn = $checkIn;
        return $this;
    }

    /**
     * التحقق من أن العملية هي تسجيل دخول
     */
    public function isCheckIn(): bool
    {
        return $this->checkType === CheckType::CHECKIN;
    }

    /**
     * التحقق من أن العملية هي تسجيل خروج
     */
    public function isCheckOut(): bool
    {
        return $this->checkType === CheckType::CHECKOUT;
    }

    /**
     * الحصول على نوع العملية من الـ payload (إذا محدد)
     */
    public function getRequestedCheckType(): ?CheckType
    {
        $type = $this->payload['type'] ?? null;
        return $type ? CheckType::tryFrom($type) : null;
    }

    /**
     * تحويل إلى مصفوفة للحفظ في قاعدة البيانات
     */
    public function toCreateArray(): array
    {
        return [
            'employee_id' => $this->employee->id,
            'period_id' => $this->workPeriod?->id,
            'check_date' => $this->shiftDate,
            'check_time' => $this->requestTime->toTimeString(),
            'day' => $this->shiftDayName,
            'check_type' => $this->checkType?->value,
            'branch_id' => $this->employee->branch_id,
            'created_by' => auth()->id() ?? 0,
            'attendance_type' => $this->attendanceType->value,
            'status' => $this->status?->value,
            'real_check_date' => $this->requestTime->toDateString(),
            'accepted' => 1,
            'delay_minutes' => $this->delayMinutes,
            'early_arrival_minutes' => $this->earlyArrivalMinutes,
            'late_departure_minutes' => $this->lateDepartureMinutes,
            'early_departure_minutes' => $this->earlyDepartureMinutes,
            'checkinrecord_id' => $this->isCheckOut() ? $this->lastCheckIn?->id : null,
            'source_type' => $this->sourceType,
            'source_id' => $this->sourceId,
        ];
    }
}
