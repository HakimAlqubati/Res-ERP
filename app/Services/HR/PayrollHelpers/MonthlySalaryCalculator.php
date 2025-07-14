<?php

namespace App\Services\HR\PayrollHelpers;

use App\Models\Employee;
use App\Models\Allowance;
use App\Models\Deduction;
use Carbon\Carbon;

class MonthlySalaryCalculator
{
    protected $allowanceCalculator;
    protected $deductionCalculator;
    protected $overtimeCalculator;
    protected $attendancePenaltyCalculator;
    protected $installmentManager;
    protected $leaveBalanceService;
    protected $payrollSettingsService;

    public function __construct(
        AllowanceCalculator $allowanceCalculator,
        DeductionCalculator $deductionCalculator,
        OvertimeCalculator $overtimeCalculator,
        AttendancePenaltyCalculator $attendancePenaltyCalculator,
        InstallmentManager $installmentManager,
        LeaveBalanceService $leaveBalanceService,
        PayrollSettingsService $payrollSettingsService,
    ) {
        $this->allowanceCalculator = $allowanceCalculator;
        $this->deductionCalculator = $deductionCalculator;
        $this->overtimeCalculator = $overtimeCalculator;
        $this->attendancePenaltyCalculator = $attendancePenaltyCalculator;
        $this->installmentManager = $installmentManager;
        $this->leaveBalanceService = $leaveBalanceService;
        $this->payrollSettingsService = $payrollSettingsService;
    }

    public function calculateMonthlySalaryV2($employeeId, $date)
    {
        // جلب بيانات الموظف والعلاوات والخصومات العامة
        $generalAllowanceTypes = Allowance::where('is_specific', 0)
            ->where('active', 1)
            ->select('name', 'is_percentage', 'amount', 'percentage', 'id')
            ->get()->toArray();

        $employee = Employee::with(['deductions', 'allowances', 'monthlyIncentives'])
            ->whereNotNull('salary')
            ->find($employeeId);

        if (!$employee) {
            return 'Employee not found!';
        }

        $daysInMonth = $this->payrollSettingsService->getDaysMonthReal($date);
        $basicSalary = $employee->salary;

        // جلب الخصومات العامة بحسب الشروط
        $generalDeducationTypes = Deduction::where('is_specific', 0)
            ->where('active', 1)
            ->select(
                'name', 'is_percentage', 'amount', 'percentage', 'id',
                'condition_applied_v2', 'nationalities_applied', 'less_salary_to_apply',
                'has_brackets', 'applied_by', 'employer_percentage', 'employer_amount'
            )
            ->with('brackets')
            ->get();

        $deduction = [];
        foreach ($generalDeducationTypes as  $deductionType) {
            if ($deductionType->condition_applied_v2 == Deduction::CONDITION_APPLIED_V2_ALL) {
                $deduction[] = $deductionType;
            }
            if (
                $deductionType->condition_applied_v2 == Deduction::CONDITION_APPLIED_V2_CITIZEN_EMPLOYEE &&
                $employee->is_citizen
            ) {
                $deduction[] = $deductionType;
            }
            if (
                $deductionType->condition_applied_v2 == Deduction::CONDITION_APPLIED_V2_CITIZEN_EMPLOYEE_AND_FOREIGN_HAS_PASS &&
                ($employee->is_citizen || ($employee->has_employee_pass))
            ) {
                $deduction[] = $deductionType;
            }
        }

        // حساب العلاوات العامة
        $generalAllowanceResultCalculated = $this->allowanceCalculator->calculateAllowances($generalAllowanceTypes, $basicSalary);

        // العلاوات والخصومات المخصصة للموظف
        $specificDeductions = $employee->deductions->map(function ($deduction) {
            return [
                'is_percentage' => $deduction->is_percentage,
                'amount' => $deduction->amount,
                'percentage' => $deduction->percentage,
                'id' => $deduction->deduction_id,
                'name' => $deduction->deduction->name,
                'has_brackets' => $deduction->deduction->has_brackets,
                'applied_by' => $deduction->deduction->applied_by,
            ];
        })->toArray();

        $specificAllowances = $employee->allowances->map(function ($allowance) {
            return [
                'is_percentage' => $allowance->is_percentage,
                'amount' => $allowance->amount,
                'percentage' => $allowance->percentage,
                'id' => $allowance->allowance_id,
                'name' => $allowance->allowance->name,
            ];
        })->toArray();

        $specificAlloanceCalculated = $this->allowanceCalculator->calculateAllowances($specificAllowances, $basicSalary);
        $specificDeducationCalculated = $this->deductionCalculator->calculateDeductions($specificDeductions, $basicSalary);

        $deducationInstallmentAdvancedMonthly = $this->installmentManager->getInstallmentAdvancedMonthly($employee, date('Y', strtotime($date)), date('m', strtotime($date)));

        $totalMonthlyIncentives = $employee->monthlyIncentives->sum('amount');

        // الراتب اليومي والساعة
        $dailySalary = $this->getDailySalary($employee, $date);
        $hourlySalary = $this->getHourlySalary($employee, $date);

        // الضريبة السنوية
        $taxDeduction = $this->deductionCalculator->calculateYearlyTax($employee);

        // تواريخ الحضور
        $carbonDate = Carbon::parse($date);
        $startDate = $carbonDate->copy()->startOfMonth()->format('Y-m-d');
        $endDate = $carbonDate->copy()->endOfMonth()->format('Y-m-d');
        $attendances = employeeAttendances($employeeId, $startDate, $endDate);

        if ($attendances == 'no_periods') {
            return 'no_periods';
        }

        // احتساب الغياب والتأخير
        $totalAbsentDays = 0;
        $totalNoPeriodDays = 0;
        $totalAttendanceDays = 0;
        $diffirenceBetweenAttendanceAbsentDays = 0;
        $absentDates = [];

        if (!$employee->discount_exception_if_absent) {
            $absentData = $this->attendancePenaltyCalculator->calculateTotalAbsentDays($attendances);
            $totalAbsentDays = $absentData['total_absent_days'];
            $totalNoPeriodDays = $absentData['total_no_period_days'];
            $totalAttendanceDays = $absentData['total_attendance_days'];
            $diffirenceBetweenAttendanceAbsentDays = $absentData['difference'];
            $absentDates = $absentData['absent_dates'];
        }

        $differneceBetweenDaysMonthAndAttendanceDays = $daysInMonth - $totalAttendanceDays;
        $totalAbsentDays += $totalNoPeriodDays;
        $totalLateHours = 0;
        $totalEarlyDepatureHours = 0;

        if (!$employee->discount_exception_if_attendance_late) {
            $totalLateHours = $this->attendancePenaltyCalculator->calculateTotalLateArrival($attendances)['totalHoursFloat'];
            $totalEarlyDepatureHours = $this->attendancePenaltyCalculator->calculateTotalEarlyLeave($attendances);
        }

        $totalMissingHours = $this->attendancePenaltyCalculator->calculateTotalMissingHours($attendances);

        // الأوفر تايم
        $overtimeHours = $this->overtimeCalculator->getEmployeeOvertimes($date, $employee);
        $overtimePay = $overtimeHours * $hourlySalary * setting('overtime_hour_multiplier');

        // الإجازة الشهرية
        $monthlyLeaveBalance = $this->leaveBalanceService->getLeaveMonthlyBalance($employee, $date);
        $overtimeBasedOnMonthlyLeave = $this->overtimeCalculator->createEmployeeOverime($employee, $date);

        $autoWeeklyLeaveData = calculateAutoWeeklyLeaveData($date, $employeeId);
        $overtimeBasedOnMonthlyLeavePay = $dailySalary * $autoWeeklyLeaveData['remaining_leaves'];

        $realTotalAbsentDays = $totalAbsentDays;

        // حساب الخصومات
        $deductionForAbsentDays = $totalAbsentDays * $dailySalary;
        $realDeductionForAbsentDays = $realTotalAbsentDays * $dailySalary;
        $deductionForLateHours = $totalLateHours * $hourlySalary;
        $deductionForMissingHours = round(($totalMissingHours['total_hours'] ?? 0) * $hourlySalary, 2);
        $deductionForEarlyDepatureHours = $totalEarlyDepatureHours * $hourlySalary;

        // احتساب النتائج
        $totalDeducations = ($specificDeducationCalculated['result']  + $deductionForLateHours + $deductionForEarlyDepatureHours + $deductionForAbsentDays + ($deducationInstallmentAdvancedMonthly?->installment_amount ?? 0) + $taxDeduction + $deductionForMissingHours);
        $totalAllowances = ($specificAlloanceCalculated['result'] + $generalAllowanceResultCalculated['result']);
        $totalOtherAdding = ($overtimePay + $totalMonthlyIncentives + $overtimeBasedOnMonthlyLeavePay);

        $netSalary = ($basicSalary + $totalAllowances + $totalOtherAdding) - $totalDeducations;
        $remaningSalary = round($netSalary - round($totalDeducations, 2), 2);

        // حساب الخصومات العامة الإضافية
        $deductionEmployer = collect($deduction)->whereIn('applied_by', [Deduction::APPLIED_BY_BOTH, Deduction::APPLIED_BY_EMPLOYER])->toArray();
        $generalDedeucationResultCalculated = $this->deductionCalculator->calculateDeductions($deduction, $netSalary);
        $dedeucationResultCalculatedEmployer = $this->deductionCalculator->calculateDeductionsEmployeer($deductionEmployer, $netSalary);
        $totalDeducations +=  $generalDedeucationResultCalculated['result'];
        $netSalary = $this->replaceZeroInstedNegative($netSalary) - $generalDedeucationResultCalculated['result'];

        return [
            'net_salary' => round($netSalary, 2),
            'details' => [
                'basic_salary' => ($basicSalary),
                'salary_after_deducation' => $this->replaceZeroInstedNegative($remaningSalary),
                'deducation_installment_advanced_monthly' => [
                    'amount' => $deducationInstallmentAdvancedMonthly?->installment_amount,
                    'installment_id' => $deducationInstallmentAdvancedMonthly?->id
                ],
                'ins' => $deducationInstallmentAdvancedMonthly?->installment_amount,
                'tax_deduction' => round($taxDeduction, 2),
                'totalMissingHours' => $totalMissingHours,
                'deductionForMissingHours' => $deductionForMissingHours,
                'total_deducation' => round($totalDeducations, 2),
                'total_allowances' => round($totalAllowances, 2),
                'total_other_adding' => round($totalOtherAdding, 2),
                'specific_deducation_result' => round($specificDeducationCalculated['result'], 2),
                'specific_allowances_result' => round($specificAlloanceCalculated['result'], 2),
                'general_deducation_result' => round($generalDedeucationResultCalculated['result'], 2),
                'general_allowances_result' => round($generalAllowanceResultCalculated['result'], 2),
                'deduction_for_absent_days' => round($deductionForAbsentDays, 2),
                'realDeductionForAbsentDays' => round($realDeductionForAbsentDays, 2),
                'deduction_for_late_hours' => round($deductionForLateHours, 2),
                'deduction_for_early_depature_hours' => round($deductionForEarlyDepatureHours, 2),
                'total_monthly_incentives' => $totalMonthlyIncentives,
                'overtime_pay' => round($overtimePay, 2),
                'overtime_hours' => $overtimeHours,
                'monthly_leave_balance' => $monthlyLeaveBalance,
                'weekendOverTimeDays' => 0, // يمكنك تطويرها لاحقا
                'autoWeeklyLeaveData' => $autoWeeklyLeaveData,
                'overtime_based_on_monthly_leave' => $overtimeBasedOnMonthlyLeave,
                'overtime_based_on_monthly_leave_pay' => $overtimeBasedOnMonthlyLeavePay,
                'total_absent_days' => $totalAbsentDays,
                'realTotalAbsentDays' => $realTotalAbsentDays,
                'absent_dates' => $absentDates,
                'total_late_hours' => $totalLateHours,
                'total_early_depature_hours' => $totalEarlyDepatureHours,
                'deducation_details' => [
                    'specific_deducation' => $specificDeducationCalculated,
                    'general_deducation' => $generalDedeucationResultCalculated,
                    'general_deducation_employer' => $dedeucationResultCalculatedEmployer,
                ],
                'adding_details' => [
                    'specific_allowances' => $specificAlloanceCalculated,
                    'general_allowances' => $generalAllowanceResultCalculated,
                ],
                'another_details' => [
                    'daily_salary' => $dailySalary,
                    'hourly_salary' => $hourlySalary,
                    'days_in_month' => $daysInMonth,
                    'differneceBetweenDaysMonthAndAttendanceDays' => $differneceBetweenDaysMonthAndAttendanceDays,
                ],
            ],
        ];
    }

    // دوال مساعدة
    protected function getDailySalary($employee, $date)
    {
        // حساب الراتب اليومي (يمكن تطويره لاحقا)
        $daysInMonth = $this->payrollSettingsService->getDaysInMonth($date);
        return round($employee->salary / $daysInMonth, 2);
    }

    protected function getHourlySalary($employee, $date)
    {
        // حساب الراتب بالساعة (يمكن تطويره لاحقا)
        $dailySalary = $this->getDailySalary($employee, $date);
        return round($dailySalary / ($employee->working_hours ?: 1), 2);
    }

    protected function replaceZeroInstedNegative($value)
    {
        return $value < 0 ? 0 : $value;
    }
}