<?php

namespace App\Http\Resources;

use App\Models\Attendance;
use App\Services\HR\AttendanceHelpers\Reports\CalculateMissingHours;
use Illuminate\Http\Resources\Json\JsonResource;

class CheckOutAttendanceResource extends JsonResource
{
    protected $approvedOvertime;
    protected $date;
    protected $employeeDiscountException;
    protected $dayAttendancesCol;

    public function __construct($resource, $approvedOvertime = null, $date = null, $employeeDiscountException = null, $dayAttendancesCol = null)
    {
        parent::__construct($resource);
        $this->approvedOvertime = $approvedOvertime;
        $this->date             = $date;
        $this->employeeDiscountException = $employeeDiscountException;
        $this->dayAttendancesCol = $dayAttendancesCol;
    }

    public function toArray($request)
    {
        $supposedStatus = $this->resolveSupposedStatus();

        $earlyDepartureMinutes = $this->early_departure_minutes;
        if ($earlyDepartureMinutes > 0 && $this->total_actual_duration_hourly && $this->supposed_duration_hourly) {
            $actualHoursFloat = \App\Services\HR\AttendanceHelpers\Reports\HelperFunctions::timeToHoursFloat((string) $this->total_actual_duration_hourly);
            $supposedHoursFloat = \App\Services\HR\AttendanceHelpers\Reports\HelperFunctions::timeToHoursFloat((string) $this->supposed_duration_hourly);

            $margin = 0;
            if (function_exists('setting') && setting('flix_hours_early_departure')) {
                $margin = \App\Services\HR\AttendanceHelpers\Reports\HelperFunctions::FLEXIBLE_HOURS_MARGIN_MINUTES / 60;
            }

            if ($actualHoursFloat >= ($supposedHoursFloat - $margin)) {
                $earlyDepartureMinutes = 0;
            } else {
                $maxEarlyHours = max(0, $supposedHoursFloat - $actualHoursFloat);
                $maxEarlyMinutes = (int) round($maxEarlyHours * 60);

                if ($earlyDepartureMinutes > $maxEarlyMinutes) {
                    $earlyDepartureMinutes = $maxEarlyMinutes;
                }
            }
        }

        return [
            'id'                       => $this->id,
            'branch_id'                => $this->branch_id,
            'check_time'               => $this->check_time,
            'late_departure_minutes'   => $this->late_departure_minutes,
            'early_departure_minutes'  => $earlyDepartureMinutes,
            'actual_duration_hourly'   => $this->actual_duration_hourly,
            'total_actual_duration_hourly'   => $this->total_actual_duration_hourly,
            'supposed_duration_hourly' => $this->supposed_duration_hourly,
            // 'status'                   => $this->status,
            // 'status_label'             => Attendance::getStatusLabel($this->status),
            'status'                   => $supposedStatus,
            'status_label'             => Attendance::getStatusLabel($supposedStatus),
            'supposed_status'          => $supposedStatus,
            'supposed_status_label'    => Attendance::getStatusLabel($supposedStatus),
            'missing_hours'            => (new CalculateMissingHours())->calculate(
                $this->status,
                $this->supposed_duration_hourly ?? $this->period . ':00',
                $this->approvedOvertime,
                $this->date,
                $this->employee_id,
                $this->total_actual_duration_hourly,
                $this->employeeDiscountException,
                $this->dayAttendancesCol
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

            if ($earlyMinutes > 0 && $this->total_actual_duration_hourly && $this->supposed_duration_hourly) {
                $actualHoursFloat = \App\Services\HR\AttendanceHelpers\Reports\HelperFunctions::timeToHoursFloat((string) $this->total_actual_duration_hourly);
                $supposedHoursFloat = \App\Services\HR\AttendanceHelpers\Reports\HelperFunctions::timeToHoursFloat((string) $this->supposed_duration_hourly);

                $margin = 0;
                if (function_exists('setting') && setting('flix_hours_early_departure')) {
                    $margin = \App\Services\HR\AttendanceHelpers\Reports\HelperFunctions::FLEXIBLE_HOURS_MARGIN_MINUTES / 60;
                }

                if ($actualHoursFloat >= ($supposedHoursFloat - $margin)) {
                    $earlyMinutes = 0;
                } else {
                    $maxEarlyHours = max(0, $supposedHoursFloat - $actualHoursFloat);
                    $maxEarlyMinutes = (int) round($maxEarlyHours * 60);

                    if ($earlyMinutes > $maxEarlyMinutes) {
                        $earlyMinutes = $maxEarlyMinutes;
                    }
                }
            }

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
