<?php

namespace App\Http\Resources;

use App\Models\Attendance;
use App\Services\HR\AttendanceHelpers\Reports\HelperFunctions;
use Illuminate\Http\Resources\Json\JsonResource;

class CheckOutAttendanceResource extends JsonResource
{
    protected $approvedOvertime;
    protected $date;
    protected HelperFunctions $helperFunctions;

    public function __construct($resource, $approvedOvertime = null, $date = null)
    {
        // أولاً مرر الموديل للـ parent
        parent::__construct($resource);
        $this->approvedOvertime = $approvedOvertime;
        $this->date             = $date;
        $this->helperFunctions = new HelperFunctions();
    }

    public function toArray($request)
    {
        $supposedStatus = $this->resolveSupposedStatus();

        return [
            'id'                       => $this->id,
            'check_time'               => $this->check_time,
            'late_departure_minutes'   => $this->late_departure_minutes,
            'early_departure_minutes'  => $this->early_departure_minutes,
            'actual_duration_hourly'   => $this->actual_duration_hourly,
            'total_actual_duration_hourly'   => $this->total_actual_duration_hourly,
            'supposed_duration_hourly' => $this->supposed_duration_hourly,
            // 'status'                   => $this->status,
            // 'status_label'             => Attendance::getStatusLabel($this->status),
            'status'                   => $supposedStatus,
            'status_label'             => Attendance::getStatusLabel($supposedStatus),
            'supposed_status'          => $supposedStatus,
            'supposed_status_label'    => Attendance::getStatusLabel($supposedStatus),
            'missing_hours'            => $this->helperFunctions->calculateMissingHours(
                $this->status,
                $this->supposed_duration_hourly ?? $this->period . ':00',
                $this->approvedOvertime,
                $this->date,
                $this->employee_id,

            ),
            // حقول أخرى تخص الانصراف فقط
        ];
    }

    /**
     * إعادة حساب الحالة بناءً على الإعدادات الحالية
     * بدون تعديل الحالة الأصلية المخزنة في DB
     */
    protected function resolveSupposedStatus(): string
    {
        $status = $this->status;

        // إعادة تقييم حالة الانصراف المبكر حسب فترة السماح
        if ($status === Attendance::STATUS_EARLY_DEPARTURE) {
            $earlyMinutes = (int) ($this->early_departure_minutes ?? 0);
            $graceMinutes = (int) settingWithDefault('early_depature_deduction_minutes', 0);

            if ($earlyMinutes <= $graceMinutes) {
                return Attendance::STATUS_ON_TIME;
            }
        }

        // إعادة تقييم حالة الانصراف المتأخر حسب فترة السماح
        if ($status === Attendance::STATUS_LATE_DEPARTURE) {
            $lateMinutes = (int) ($this->late_departure_minutes ?? 0);
            $graceMinutes = (int) settingWithDefault('early_depature_deduction_minutes', 0);

            if ($lateMinutes <= $graceMinutes) {
                return Attendance::STATUS_ON_TIME;
            }
        }

        return $status ?? '';
    }
}
