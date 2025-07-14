<?php

namespace App\Http\Controllers\HR;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Services\HR\SalaryHelpers\AttendanceSalaryCalculator;

class TestSalaryCalcController extends Controller
{
    public function show(Employee $employee, $year, $month)
    {
        // بيانات افتراضية للتجربة
        $absentDays    = 2;
        $lateMinutes   = 55;
        $overtimeHours = 7.25;
        $basicSalary   = $employee->salary ?? 100000;
        $workingDays   = 30;
        $workHoursPerDay = 8;

        // حساب الأجرة اليومية والساعة والدقيقة
        $dayWage    = AttendanceSalaryCalculator::getDayWage($basicSalary, $workingDays);
        $hourWage   = AttendanceSalaryCalculator::getHourWage($basicSalary, $workingDays, $workHoursPerDay);
        $minuteWage = AttendanceSalaryCalculator::getMinuteWage($basicSalary, $workingDays, $workHoursPerDay);

        // حساب الخصومات والحوافز
        $absenceDeduction = AttendanceSalaryCalculator::calculateAbsenceDeduction(
            $absentDays, $basicSalary, $workingDays
        );
        $lateDeduction = AttendanceSalaryCalculator::calculateLateDeduction(
            $lateMinutes, $basicSalary, $workingDays, $workHoursPerDay
        );
        $overtimeHourRate = AttendanceSalaryCalculator::calculateOvertimeHourValue(
            $basicSalary, $workingDays, $workHoursPerDay, 1.5
        );
        $overtimeBonus = AttendanceSalaryCalculator::calculateOvertimeBonus(
            $overtimeHours, $overtimeHourRate
        );

        $net = $basicSalary + $overtimeBonus - $absenceDeduction - $lateDeduction;

        return view('test_salary_calc', [
            'employee'          => $employee,
            'year'              => $year,
            'month'             => $month,
            'basicSalary'       => $basicSalary,
            'absentDays'        => $absentDays,
            'lateMinutes'       => $lateMinutes,
            'overtimeHours'     => $overtimeHours,
            'overtimeHourRate'  => $overtimeHourRate,
            'absenceDeduction'  => $absenceDeduction,
            'lateDeduction'     => $lateDeduction,
            'overtimeBonus'     => $overtimeBonus,
            'net'               => $net,
            'dayWage'           => $dayWage,
            'hourWage'          => $hourWage,
            'minuteWage'        => $minuteWage,
        ]);
    }
}