<?php

namespace App\Http\Resources;

use App\Models\Attendance;
use Illuminate\Http\Resources\Json\JsonResource;

class CheckInAttendanceResource extends JsonResource
{
    protected $checkoutData;

    public function __construct($resource, $checkoutData = null)
    {
        parent::__construct($resource);
        $this->checkoutData = $checkoutData;
    }

    public function toArray($request)
    {
        $supposedStatus = $this->resolveSupposedStatus();

        $delayMinutes = $this->delay_minutes;

        if ($delayMinutes > 0 && isset($this->checkoutData['total_actual_duration_hourly']) && isset($this->checkoutData['supposed_duration_hourly'])) {
            $actualHoursFloat = \App\Services\HR\AttendanceHelpers\Reports\HelperFunctions::timeToHoursFloat((string) $this->checkoutData['total_actual_duration_hourly']);
            $supposedHoursFloat = \App\Services\HR\AttendanceHelpers\Reports\HelperFunctions::timeToHoursFloat((string) $this->checkoutData['supposed_duration_hourly']);

            $diffMinutes = max(0, ($supposedHoursFloat - $actualHoursFloat) * 60);

            // We only adjust if the diff is LESS than the delay.
            // If they stayed late, diffMinutes will be small.
            // If they stayed MORE than supposed, diffMinutes will be 0.
            if ($diffMinutes < $delayMinutes) {
                $delayMinutes = (int) round($diffMinutes);
            }
        }

        return [
            'id'                    => $this->id,
            'branch_id'             => $this->branch_id,
            'check_time'            => $this->check_time,
            'delay_minutes'         => $delayMinutes,
            'early_arrival_minutes' => $this->early_arrival_minutes,
            // 'status'                => $this->status,
            // 'status_label'          => Attendance::getStatusLabel($this->status),
            'status'                => $supposedStatus,
            'status_label'          => Attendance::getStatusLabel($supposedStatus),
            'supposed_status'       => $supposedStatus,
            'supposed_status_label' => Attendance::getStatusLabel($supposedStatus),
            // حقول أخرى تخص الدخول فقط
        ];
    }

    /**
     * إعادة حساب الحالة بناءً على الإعدادات الحالية
     * بدون تعديل الحالة الأصلية المخزنة في DB
     */
    protected function resolveSupposedStatus(): string
    {
        $status = $this->status;

        // إعادة تقييم حالة التأخير حسب فترة السماح
        if ($status === Attendance::STATUS_LATE_ARRIVAL) {
            $delayMinutes = (int) ($this->delay_minutes ?? 0);
            $graceMinutes = (int) settingWithDefault('early_attendance_minutes', 0);

            if ($delayMinutes <= $graceMinutes) {
                return Attendance::STATUS_ON_TIME;
            }
        }

        // إعادة تقييم حالة الوصول المبكر حسب فترة السماح
        if ($status === Attendance::STATUS_EARLY_ARRIVAL) {
            $earlyMinutes = (int) ($this->early_arrival_minutes ?? 0);
            $graceMinutes = (int) settingWithDefault('early_attendance_minutes', 0);

            if ($earlyMinutes <= $graceMinutes) {
                return Attendance::STATUS_ON_TIME;
            }
        }

        return $status ?? '';
    }
}
