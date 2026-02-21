<?php

namespace App\Http\Resources;

use App\Models\Attendance;
use Illuminate\Http\Resources\Json\JsonResource;

class CheckInAttendanceResource extends JsonResource
{
    public function toArray($request)
    {
        $supposedStatus = $this->resolveSupposedStatus();

        return [
            'id'                    => $this->id,
            'check_time'            => $this->check_time,
            'delay_minutes'         => $this->delay_minutes,
            'early_arrival_minutes' => $this->early_arrival_minutes,
            'status'                => $this->status,
            'status_label'          => Attendance::getStatusLabel($this->status),
            // 'status'                => $supposedStatus,
            // 'status_label'          => Attendance::getStatusLabel($supposedStatus),
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
