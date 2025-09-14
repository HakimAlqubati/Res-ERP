<?php
namespace App\Services\HR\SalaryHelpers;

class AttendanceSalaryCalculator
{
    /**
     * حساب خصم الغياب الشهري.
     *
     * @param int $absentDays عدد أيام الغياب غير المبرر
     * @param float $basicSalary الراتب الأساسي الشهري
     * @param int $workingDays عدد أيام الدوام الرسمية في الشهر (مثلاً 26 أو 30)
     * @return float قيمة الخصم للغياب
     */
    public static function calculateAbsenceDeduction(int $absentDays, float $basicSalary, int $workingDays = 30): float
    {
        if ($absentDays <= 0 || $basicSalary <= 0 || $workingDays <= 0) {
            return 0;
        }

        $dayValue = $basicSalary / $workingDays;
        return round($absentDays * $dayValue, 2);
    }

    /**
     * حساب خصم التأخير الشهري.
     *
     * @param int $lateMinutes مجموع دقائق التأخير خلال الشهر
     * @param float $basicSalary الراتب الأساسي الشهري
     * @param int $workingDays عدد أيام الدوام الرسمية في الشهر
     * @param int $workHoursPerDay عدد ساعات العمل الرسمية يومياً (مثلاً 8)
     * @return float قيمة الخصم للتأخير
     */
    public static function calculateLateDeduction(int $lateMinutes, float $basicSalary, int $workingDays = 30, int $workHoursPerDay = 8): float
    {
        if ($lateMinutes <= 0 || $basicSalary <= 0 || $workingDays <= 0 || $workHoursPerDay <= 0) {
            return 0;
        }

        $totalWorkMinutes = $workingDays * $workHoursPerDay * 60;
        $minuteValue      = $basicSalary / $totalWorkMinutes;
        return round($lateMinutes * $minuteValue, 2);
    }

    /**
     * حساب حافز العمل الإضافي الشهري.
     *
     * @param int|float $overtimeHours مجموع ساعات العمل الإضافي في الشهر (يمكن أن يكون كسري)
     * @param float $overtimeHourRate سعر ساعة العمل الإضافي (أو استخدم معادلة ضعف/1.5 ساعة)
     * @return float قيمة حافز الإضافي
     */
    public static function calculateOvertimeBonus($overtimeHours, float $overtimeHourRate): float
    {
        if ($overtimeHours <= 0 || $overtimeHourRate <= 0) {
            return 0;
        }

        return round($overtimeHours * $overtimeHourRate, 2);
    }

    /**
     * احسب قيمة ساعة العمل الإضافي حسب سياسة الشركة (1.5 × أجر الساعة العادي مثلاً)
     * يمكنك تمرير هذا المبلغ لدالة calculateOvertimeBonus
     */
    public static function calculateOvertimeHourValue(float $basicSalary, int $workingDays = 30, int $workHoursPerDay = 8, float $multiplier = 1.5): float
    {
        if ($basicSalary <= 0 || $workingDays <= 0 || $workHoursPerDay <= 0) {
            return 0;
        }

        $hourlyRate = $basicSalary / ($workingDays * $workHoursPerDay);
        return round($hourlyRate * $multiplier, 2);
    }

    /**
     * احسب قيمة الأجرة اليومية
     *
     * @param float $basicSalary الراتب الأساسي الشهري
     * @param int $workingDays عدد أيام العمل الفعلية في الشهر (مثلاً 26 أو 30)
     * @return float قيمة الأجرة اليومية
     */
    public static function getDayWage(float $basicSalary, int $workingDays = 30): float
    {
        if ($basicSalary <= 0 || $workingDays <= 0) {
            return 0;
        }

        return round($basicSalary / $workingDays, 2);
    }

    /**
     * احسب قيمة الأجرة بالساعة
     *
     * @param float $basicSalary الراتب الأساسي الشهري
     * @param int $workingDays عدد أيام العمل في الشهر
     * @param int $workHoursPerDay عدد ساعات العمل يومياً
     * @return float قيمة الأجرة بالساعة
     */
    public static function getHourWage(float $basicSalary, int $workingDays = 30, int $workHoursPerDay = 8): float
    {
        if ($basicSalary <= 0 || $workingDays <= 0 || $workHoursPerDay <= 0) {
            return 0;
        }

        return round($basicSalary / ($workingDays * $workHoursPerDay), 2);
    }

    /**
     * احسب قيمة الأجرة بالدقيقة
     *
     * @param float $basicSalary الراتب الأساسي الشهري
     * @param int $workingDays عدد أيام العمل في الشهر
     * @param int $workHoursPerDay عدد ساعات العمل يومياً
     * @return float قيمة الأجرة بالدقيقة
     */
    public static function getMinuteWage(float $basicSalary, int $workingDays = 30, int $workHoursPerDay = 8): float
    {
        if ($basicSalary <= 0 || $workingDays <= 0 || $workHoursPerDay <= 0) {
            return 0;
        }

        return round($basicSalary / ($workingDays * $workHoursPerDay * 60), 4); // أربع منازل عشرية للدقة
    }
}