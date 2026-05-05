<?php

namespace App\Modules\HR\Attendance\Services;

use App\Models\Setting;

/**
 * كلاس الإعدادات المركزي للحضور
 * 
 * يوفر واجهة موحدة للوصول لإعدادات نظام الحضور
 * بدلاً من استدعاء Setting::getSetting مباشرة في كل مكان
 */
class AttendanceConfig
{
    /**
     * عدد الساعات المسموحة قبل بداية الوردية
     */
    public function getAllowedHoursBefore(): int
    {
        return (int) Setting::getSetting('hours_count_after_period_before', 0);
    }

    /**
     * عدد الساعات المسموحة بعد نهاية الوردية
     */
    public function getAllowedHoursAfter(): int
    {
        return (int) Setting::getSetting('hours_count_after_period_after', 0);
    }

    /**
     * دقائق السماحية للحضور المبكر/المتأخر
     */
    public function getGraceMinutes(): int
    {
        return (int) Setting::getSetting('early_attendance_minutes', 0);
    }

    /**
     * دقائق السماحية للتأخير
     */
    public function getLateArrivalGraceMinutes(?\App\Models\Employee $employee = null): int
    {
        if ($employee && $employee->branch && $employee->branch->type === \App\Models\Branch::TYPE_HQ) {
            return 30;
        }

        return (int) Setting::getSetting('late_attendance_grace_minutes', 10);
    }

    /**
     * دقائق السماحية للانصراف المبكر
     */
    public function getEarlyDepartureGraceMinutes(): int
    {
        return (int) Setting::getSetting('early_depature_deduction_minutes', 0);
    }

    /**
     * عدد الساعات قبل نهاية الوردية التي تتطلب تحديد نوع العملية
     */
    public function getPreEndHoursForCheckInOut(): int
    {
        return (int) Setting::getSetting('pre_end_hours_for_check_in_out', 1);
    }

    /**
     * هل يُسمح بتسجيل الحضور بدون وردية؟
     */
    public function isNoShiftAttendanceAllowed(): bool
    {
        return (bool) Setting::getSetting('allow_attendance_without_shift', false);
    }

    /**
     * مدة القفل بالثواني لمنع الطلبات المتزامنة
     */
    public function getLockTimeout(): int
    {
        return 10;
    }

    /**
     * مدة انتظار القفل بالثواني
     */
    public function getLockWaitTime(): int
    {
        return 5;
    }

    /**
     * الحصول على جميع الإعدادات كمصفوفة
     */
    public function toArray(): array
    {
        return [
            'allowed_hours_before' => $this->getAllowedHoursBefore(),
            'allowed_hours_after' => $this->getAllowedHoursAfter(),
            'grace_minutes' => $this->getGraceMinutes(),
            'pre_end_hours' => $this->getPreEndHoursForCheckInOut(),
            'lock_timeout' => $this->getLockTimeout(),
            'lock_wait_time' => $this->getLockWaitTime(),
        ];
    }
}
