<?php

use App\Models\Allowance;
use App\Models\Deduction;
use App\Models\Employee;
use App\Models\EmployeeApplicationV2;
use App\Models\LeaveBalance;
use App\Models\LeaveType;
use App\Models\MonthlySalaryDeductionsDetail;
use App\Models\MonthlySalaryIncreaseDetail;
use App\Models\MonthSalary;
use Mccarlosen\LaravelMpdf\Facades\LaravelMpdf  as PDF;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * to calculate the salary
 */
function calculateMonthlySalaryV2($employeeId, $date)
{
    $generalAllowanceTypes = Allowance::where('is_specific', 0)
        ->where('active', 1)
        ->select('name', 'is_percentage', 'amount', 'percentage', 'id')
        ->get()->toArray();
    // Retrieve the employee model with relations to deductions, allowances, and incentives
    $employee = Employee::with(['deductions', 'allowances', 'monthlyIncentives'])
        ->whereNotNull('salary')
        ->find($employeeId);
    if (!$employee) {
        return 'Employee not found!';
    }




    $nationality = $employee?->nationality;
    // Basic salary from the employee model
    $basicSalary = $employee->salary;

    $generalDeducationTypes = Deduction::where('is_specific', 0)
        ->where('active', 1)
        ->select(
            'name',
            'is_percentage',
            'amount',
            'percentage',
            'id',
            'condition_applied_v2',
            'nationalities_applied',
            'less_salary_to_apply',
            'has_brackets',
            'applied_by',
            'employer_percentage',
            'employer_amount'
        )
        ->with('brackets')
        ->get();
    // dd($generalDeducationTypes);
    $deduction = [];
    foreach ($generalDeducationTypes as  $deductionType) {

        if ($deductionType->condition_applied_v2 == Deduction::CONDITION_APPLIED_V2_ALL) {
            $deduction[] = $deductionType;
        }
        if (
            $deductionType->condition_applied_v2 == Deduction::CONDITION_APPLIED_V2_CITIZEN_EMPLOYEE &&
            $employee->is_citizen
            && $deductionType->has_brackets == 1
        ) {
            $deduction[] = $deductionType;
        }
        if (
            $deductionType->condition_applied_v2 == Deduction::CONDITION_APPLIED_V2_CITIZEN_EMPLOYEE &&
            $employee->is_citizen
        ) {
            $deduction[] = $deductionType;
        }
        // if (
        //     $deductionType->condition_applied_v2 == Deduction::CONDITION_APPLIED_V2_CITIZEN_EMPLOYEE &&
        //     $employee->is_citizen
        //     && $deductionType->has_brackets == 1
        // ) {
        //     $deduction[] = $deductionType;
        // }
        if (
            $deductionType->condition_applied_v2 == Deduction::CONDITION_APPLIED_V2_CITIZEN_EMPLOYEE_AND_FOREIGN_HAS_PASS &&
            ($employee->is_citizen || ($employee->has_employee_pass))
            // && $basicSalary >= $deductionType->less_salary_to_apply
        ) {
            $deduction[] = $deductionType;
        }
    }
    // dd($deduction);
    $generalAllowanceResultCalculated = calculateAllowances($generalAllowanceTypes, $basicSalary);
    $deductionEmployer = collect($deduction)->whereIn('applied_by', [Deduction::APPLIED_BY_BOTH, Deduction::APPLIED_BY_EMPLOYER])->toArray();

    $generalDedeucationResultCalculated = calculateDeductions($deduction, $basicSalary);
    $dedeucationResultCalculatedEmployer = calculateDeductionsEmployeer($deductionEmployer, $basicSalary);

    $approvedPenaltyDeductions = $employee->getApprovedPenaltyDeductionsForPeriod(date('Y', strtotime($date)), date('m', strtotime($date)));
    $totalPenaltyDeductions = collect($approvedPenaltyDeductions)->sum('penalty_amount');
    // dd($deduction);
    // Calculate total deductions
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

    // Calculate total allowances
    $specificAllowances = $employee->allowances->map(function ($allowance) {
        return [
            'is_percentage' => $allowance->is_percentage,
            'amount' => $allowance->amount,
            'percentage' => $allowance->percentage,
            'id' => $allowance->allowance_id,
            'name' => $allowance->allowance->name, // Accessing the name from the Allowance model
        ];
    })->toArray();

    $specificAlloanceCalculated = calculateAllowances($specificAllowances, $basicSalary);
    $specificDeducationCalculated = calculateDeductions($specificDeductions, $basicSalary);
    $deducationInstallmentAdvancedMonthly = getInstallmentAdvancedMonthly($employee, date('Y', strtotime($date)), date('m', strtotime($date)));

    $totalMonthlyIncentives = $employee->monthlyIncentives->sum(function ($incentive) {
        return $incentive->amount;
    });

    // Calculate daily and hourly salary
    $dailySalary = calculateDailySalary($employeeId, $date);
    $hourlySalary = calculateHourlySalary($employee, $date);


    $taxDeduction = calculateYearlyTax($employee); // Retrieve tax percentage using the function in the Employee model

    $date = Carbon::parse($date);
    // Get the start of the month
    $startDate = $date->copy()->startOfMonth()->format('Y-m-d');

    // Get the end of the month
    $endDate = $date->copy()->endOfMonth()->format('Y-m-d');
    $attendances = employeeAttendances($employeeId, $startDate, $endDate);

    if ($attendances == 'no_periods') {
        return 'no_periods';
    }
    $totalAbsentDays = 0;
    $absentDates = [];
    if (!$employee->discount_exception_if_absent) {
        // dd(calculateTotalAbsentDays($attendances));
        $totalAbsentDays = calculateTotalAbsentDays($attendances)['total_absent_days'];
        $absentDates = calculateTotalAbsentDays($attendances)['absent_dates'];
    }


    $totalLateHours = 0;
    $totalEarlyDepatureHours = 0;
    if (!$employee->discount_exception_if_attendance_late) {
        $totalLateHours = calculateTotalLateArrival($attendances)['totalHoursFloat'];
        $totalEarlyDepatureHours = calculateTotalEarlyLeave($attendances);
    }


    $overtimeHours = getEmployeeOvertimes($date, $employee);
    // Calculate overtime pay (overtime hours paid at double the regular hourly rate)
    $overtimePay = $overtimeHours * $hourlySalary * setting('overtime_hour_multiplier');

    $monthlyLeaveBalance = getLeaveMonthlyBalance($employee, $date);
    $overtimeBasedOnMonthlyLeave = createEmployeeOverime($employee, $date);
    $overtimeBasedOnMonthlyLeavePay = 0;
    if ($overtimeBasedOnMonthlyLeave > 0) {
        $overtimeBasedOnMonthlyLeavePay = round($overtimeBasedOnMonthlyLeave * $hourlySalary, 2);
    }


    $checkForMonthlyBalanceAntCreate['result'] = null;
    $createPayrol = setting('create_auto_leave_when_create_payroll');

    if ($createPayrol) {
        $checkForMonthlyBalanceAntCreate =  checkForMonthlyBalanceAndCreateToCancelAbsent($employee, $date, $totalAbsentDays, $monthlyLeaveBalance, $absentDates);
    }


    $realTotalAbsentDays = $totalAbsentDays;
    if ($checkForMonthlyBalanceAntCreate['result'] && $createPayrol) {
        $totalAbsentDays -= $monthlyLeaveBalance;
    }
    if ($createPayrol) {
        $totalAbsentDays -= $monthlyLeaveBalance;
    }
    // Calculate net salary including overtime
    // $netSalary = $basicSalary + $totalAllowances + $totalMonthlyIncentives + $overtimePay - $totalDeductions;

    // Calculate deductions for absences and lateness
    $deductionForAbsentDays = $totalAbsentDays * $dailySalary; // Deduction for absent days
    $realDeductionForAbsentDays = $realTotalAbsentDays * $dailySalary; // Deduction for absent days

    $deductionForLateHours = $totalLateHours * $hourlySalary; // Deduction for late hours
    $deductionForEarlyDepatureHours = $totalEarlyDepatureHours * $hourlySalary; // Deduction for late hours

    $totalDeducations = ($specificDeducationCalculated['result'] + $generalDedeucationResultCalculated['result'] + $deductionForLateHours + $deductionForEarlyDepatureHours + $deductionForAbsentDays + ($deducationInstallmentAdvancedMonthly?->installment_amount ?? 0) + $taxDeduction + $totalPenaltyDeductions);
    $totalAllowances = ($specificAlloanceCalculated['result'] + $generalAllowanceResultCalculated['result'] + 0);
    $totalOtherAdding = ($overtimePay + $totalMonthlyIncentives);

    $netSalary = ($basicSalary + $totalAllowances + $totalOtherAdding) - $totalDeducations;
    $remaningSalary = round($netSalary - round($totalDeducations, 2), 2);
    $netSalary = replaceZeroInstedNegative($netSalary);
    // Return the details and net salary breakdown
    $result = [
        'net_salary' => round($netSalary, 2),
        'details' => [
            'basic_salary' => ($basicSalary),
            'salary_after_deducation' => replaceZeroInstedNegative($remaningSalary),
            'deducation_installment_advanced_monthly' => [
                'amount' => $deducationInstallmentAdvancedMonthly?->installment_amount,
                'installment_id' => $deducationInstallmentAdvancedMonthly?->id
            ],
            'ins' => $deducationInstallmentAdvancedMonthly?->installment_amount,
            'tax_deduction' => round($taxDeduction, 2), // Add tax deduction to the breakdown

            'total_deducation' => round($totalDeducations, 2),
            'totalPenaltyDeductions' => $totalPenaltyDeductions,
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
                'approved_penalty_deductions' => $approvedPenaltyDeductions,
                'general_deducation_employer' => $dedeucationResultCalculatedEmployer,
            ],
            'adding_details' => [
                'specific_allowances' => $specificAlloanceCalculated,
                'general_allowances' => $generalAllowanceResultCalculated,
            ],

            'another_details' => [
                'daily_salary' => $dailySalary,
                'hourly_salary' => $hourlySalary,
                'days_in_month' => getDaysInMonth($date),
            ],
        ],
    ];
    // dd($result['details']['deducation_details']['general_deducation_employer'][0]['deduction_amount']);
    // dd(count($result['details']['deducation_details']['general_deducation_employer']));
    return $result;
}

/**
 * to calcaulte deducations
 */
function calculateDeductions(array $deductions, float $basicSalary): array
{
    $finalDeductions = [];
    $totalDeductions = 0.0; // Initialize total deductions

    foreach ($deductions as $deduction) {
        if ($deduction['is_percentage']) {
            // Calculate the deduction based on the percentage
            $deductionAmount = ($basicSalary * $deduction['percentage']) / 100;
        } else {
            // Use the fixed amount directly
            $deductionAmount = (float) $deduction['amount'];
        }

        if (isset($deduction['has_brackets']) && $deduction['has_brackets'] && isset($deduction['brackets'])) {
            $deductionAmount = $deduction->calculateTax($basicSalary)['monthly_tax'] ?? 0;
        }
        // Add to total deductions
        $totalDeductions += $deductionAmount;

        // Store the result
        $finalDeductions[] = [
            'id' => $deduction['id'],
            'name' => $deduction['name'],
            'deduction_amount' => $deductionAmount,
            'is_percentage' => $deduction['is_percentage'],
            'amount_value' => $deduction['amount'],
            'percentage_value' => $deduction['percentage'],
            'applied_by' => $deduction['applied_by'],
        ];
    }

    // Add the total deductions to the result
    $finalDeductions['result'] = $totalDeductions;

    return $finalDeductions;
}
function calculateDeductionsEmployeer($deductions, float $basicSalary): array
{

    $finalDeductions = [];
    $totalDeductions = 0.0; // Initialize total deductions

    foreach ($deductions as $deduction) {
        if ($deduction['is_percentage']) {
            // Calculate the deduction based on the percentage
            $deductionAmount = ($basicSalary * $deduction['employer_percentage']) / 100;
        } else {
            // Use the fixed amount directly
            $deductionAmount = (float) $deduction['employer_amount'];
        }

        if (isset($deduction['has_brackets']) && $deduction['has_brackets'] && isset($deduction['brackets'])) {
            $deductionAmount = $deduction->calculateTax($basicSalary)['monthly_tax'] ?? 0;
        }
        // Add to total deductions
        $totalDeductions += $deductionAmount;

        // Store the result
        $finalDeductions[] = [
            'id' => $deduction['id'],
            'name' => $deduction['name'] . ' (Employer) ',
            'deduction_amount' => $deductionAmount,
            'is_percentage' => $deduction['is_percentage'],
            'amount_value' => $deduction['employer_amount'],
            'percentage_value' => $deduction['employer_percentage'],
        ];
    }

    // Add the total deductions to the result
    $finalDeductions['result'] = $totalDeductions;

    return $finalDeductions;
}

/**
 * to calculate allowances
 */
function calculateAllowances(array $allowances, float $basicSalary): array
{
    $finalAllowances = [];
    $totalAllowances = 0.0; // Initialize total allowances

    foreach ($allowances as $allowance) {
        if ($allowance['is_percentage']) {
            // Calculate the allowance based on the percentage
            $allowanceAmount = ($basicSalary * $allowance['percentage']) / 100;
        } else {
            // Use the fixed amount directly
            $allowanceAmount = (float) $allowance['amount'];
        }

        // Add to total allowances
        $totalAllowances += $allowanceAmount;

        // Store the result
        $finalAllowances[] = [
            'id' => $allowance['id'],
            'name' => $allowance['name'],
            'allowance_amount' => $allowanceAmount,
            'is_percentage' => $allowance['is_percentage'],
            'amount_value' => $allowance['amount'],
            'percentage_value' => $allowance['percentage'],
        ];
    }

    // Add the total allowances to the result
    $finalAllowances['result'] = $totalAllowances;

    return $finalAllowances;
}

/**
 * to calculate the daily salary
 */
function calculateDailySalary($employeeId, $date = null)
{
    // Retrieve the employee model
    $employee = Employee::find($employeeId);

    if (!$employee) {
        return 'Employee not found!';
    }

    // Basic salary from the employee model
    $basicSalary = $employee->salary;

    $daysInMonth = getDaysInMonth($date);

    // Calculate daily salary
    $dailySalary = $basicSalary / $daysInMonth;

    return round($dailySalary, 2);
}

/**
 * to calculate the hourly salary
 */
function calculateHourlySalary($employee, $date = null)
{
    // Get the daily salary using the given date
    $dailySalary = calculateDailySalary($employee->id, $date);

    if (!is_numeric($dailySalary)) {
        return $dailySalary; // Return error message from calculateDailySalary if any
    }

    // Calculate hourly salary assuming an 8-hour workday
    $hourlySalary = $dailySalary / $employee->working_hours;

    return round($hourlySalary, 2);
}

/**
 * to calculate days in month
 */
function getDaysInMonth($date)
{
    return setting('days_in_month');
    // If date is not provided, use the current date
    $date = $date ?? date('Y-m-d');

    // Extract the month and year from the provided date
    $currentMonth = date('m', strtotime($date));
    $currentYear = date('Y', strtotime($date));

    // Calculate the number of days in the given month and year
    $daysInMonth = cal_days_in_month(CAL_GREGORIAN, $currentMonth, $currentYear);
    return $daysInMonth;
}

/**
 *   get overtime for employee
 *  */
function getEmployeeOvertimes($date, $employee)
{
    $month = \Carbon\Carbon::parse($date)->month; // Get the month from the given date

    // Filter the overtimes to only include those that match the same month
    $overtimesForMonth = $employee->overtimesofMonth($date)
        ->get() // Retrieve the collection first
        ->filter(function ($overtime) use ($month) {
            return \Carbon\Carbon::parse($overtime->date)->month == $month;
        });

    $totalHours = $overtimesForMonth->sum(function ($overtime) {
        return (float) $overtime->hours; // Ensure the 'hours' value is cast to float
    });
    return $totalHours;
}

/**
 * create overtime basedon monthly leave
 */
function createEmployeeOverime($employee, $date)
{
    $year = Carbon::parse($date)->year; // Extracts the year
    $month = Carbon::parse($date)->month; // Extracts the month
    $monthlyBalance = LeaveBalance::getMonthlyBalanceForEmployee($employee->id, $year, $month)?->balance ?? 0;
    $totalDayHours = $employee?->working_hours ?? 0;
    return ($monthlyBalance * $totalDayHours);
}

/**
 * get leave monthly balance
 */
function getLeaveMonthlyBalance($employee, $yearAndMonth)
{
    $year = Carbon::parse($yearAndMonth)->year; // Extracts the year
    $month = Carbon::parse($yearAndMonth)->month; // Extracts the month
    $monthlyBalance = LeaveBalance::getMonthlyBalanceForEmployee($employee->id, $year, $month)?->balance ?? 0;
    return $monthlyBalance;
}
function getEmployeeOvertimesOfSpecificDate($date, $employee)
{
    $month = \Carbon\Carbon::parse($date)->month; // Get the month from the given date

    // Filter the overtimes to only include those that match the same month
    $overtimesForMonth = $employee->overtimesByDate($date)
        ->get() // Retrieve the collection first
        ->filter(function ($overtime) use ($month) {
            return \Carbon\Carbon::parse($overtime->date)->month == $month;
        });

    $totalHours = $overtimesForMonth->sum(function ($overtime) {
        return (float) $overtime->hours; // Ensure the 'hours' value is cast to float
    });


    return $totalHours;
}
function getEmployeeOvertimesV2($date, $employee)
{
    $month = \Carbon\Carbon::parse($date)->month; // Get the month from the given date

    // Filter the overtimes to only include those that match the same month
    $overtimesForMonth = $employee->overtimes->filter(function ($overtime) use ($month) {
        return \Carbon\Carbon::parse($overtime->date)->month == $month;
    });

    $totalHours = $overtimesForMonth->sum(function ($overtime) {
        return (float) $overtime->hours; // Ensure the 'hours' value is cast to float
    });

    return [$totalHours, $date];
}

function calculateAbsentDaysAndDeductSalary($empId, $date)
{
    return calculateMonthlySalaryV2($empId, $date);
}

/**
 * to get data of salary slip
 */

function employeeSalarySlip($employeeId, $sid)
{
    $monthSalary = MonthSalary::with([
        'details' => function ($query) use ($employeeId) {
            $query->where('employee_id', $employeeId);
        },
        'increaseDetails' => function ($query) use ($employeeId) {
            $query->where('employee_id', $employeeId);
        },
        'deducationDetails' => function ($query) use ($employeeId) {
            $query->where('employee_id', $employeeId);
        },
    ])->find($sid);

    return $monthSalary;
}

function convertToUtf8($data)
{
    if (is_array($data)) {
        return array_map('convertToUtf8', $data);
    } elseif (is_object($data)) {
        foreach (get_object_vars($data) as $key => $value) {
            $data->$key = convertToUtf8($value);
        }
        return $data;
    } elseif (is_string($data)) {
        // Ensure the string is properly encoded in UTF-8
        return mb_convert_encoding($data, 'UTF-8', 'UTF-8');
    }
    // Return other data types (e.g., int, float) as-is
    return $data;
}

function generateSalarySlipPdf_($employeeId, $sid)
{
    $employee = Employee::find($employeeId);
    $branch = $employee->branch;
    $data = employeeSalarySlip($employeeId, $sid);

    $increaseDetails = $data->increaseDetails;
    $deducationDetails = $data->deducationDetails;
    $allowanceTypes = Allowance::where('active', 1)->pluck('name', 'id')->toArray();
    $constAllowanceTypes = MonthlySalaryIncreaseDetail::ALLOWANCE_TYPES;
    $allowanceTypes = $allowanceTypes + $constAllowanceTypes;
    $month = $data->month;
    $monthName = Carbon::parse($month)->translatedFormat('F Y');
    $allowanceTypes = array_reverse($allowanceTypes, true);
    $employeeAllowances = collect($increaseDetails)->map(function ($allowance) use ($allowanceTypes) {
        $typeId = $allowance['type_id'];

        return [
            'id' => $allowance['id'],
            'type_id' => $typeId,
            'allowance_name' => $allowanceTypes[$typeId] ?? 'Unknown Allowance', // Fallback if allowance type is missing
            'amount' => $allowance['amount'],
        ];
    })->toArray();

    // Calculate the total allowance amount
    $totalAllowanceAmount = collect($employeeAllowances)->sum('amount') + ($data?->details[0]['overtime_pay'] ?? 0) + ($employee?->salary ?? 0) + ($data?->details[0]['total_incentives'] ?? 0);

    $deducationTypes = Deduction::where('active', 1)
        ->select('name', 'id')->pluck('name', 'id')
        ->toArray();

    $constDeducationTypes = MonthlySalaryDeductionsDetail::DEDUCTION_TYPES;
    $allDeductionTypes = $deducationTypes + $constDeducationTypes;
    $employeeDeductions = collect($deducationDetails)->map(function ($deduction) use ($allDeductionTypes) {
        $deductionId = $deduction['deduction_id'];

        return [
            'id' => $deduction['id'],
            'deduction_id' => $deductionId,
            // 'deduction_name' => $allDeductionTypes[$deductionId] ?? 'Unknown Deduction', // Fallback if deduction type is missing
            'deduction_name' => $deduction['deduction_name'] ?? 'Unknown Deduction',
            'deduction_amount' => $deduction['deduction_amount'],
        ];
    })->toArray();


    // Calculate the total deduction amount
    $totalDeductionAmount = collect($employeeDeductions)->sum('deduction_amount');

    try {
        // Prepare Data for the Blade View
        $viewData = compact(
            'data',
            'totalAllowanceAmount',
            'totalDeductionAmount',
            'employeeAllowances',
            'employeeDeductions',
            'month',
            'monthName',
            'employee',
            'branch'
        );
        $utf8Data = convertToUtf8($viewData);
        // Generate PDF
        // Generate the PDF content
        //   dd($utf8Data);
        $pdf = Pdf::loadView('export.reports.hr.salaries.salary-slip', $utf8Data);
        $pdfContent = $pdf->output();

        // Use `response()->streamDownload()`
        return response()->streamDownload(function () use ($pdfContent) {
            echo $pdfContent;
        }, 'salary-slip_' . $employee->name . '.pdf');
    } catch (\Exception $e) {
        dd($e->getMessage());
    }
}


function generateSalarySlipPdf($employeeId, $sid)
{
    $employee = Employee::find($employeeId);
    $branch = $employee->branch;
    $data = employeeSalarySlip($employeeId, $sid);

    $increaseDetails = $data->increaseDetails;
    $deducationDetails = $data->deducationDetails;
    $allowanceTypes = Allowance::where('active', 1)->pluck('name', 'id')->toArray();
    $constAllowanceTypes = MonthlySalaryIncreaseDetail::ALLOWANCE_TYPES;
    $allallowanceTypes = $allowanceTypes + $constAllowanceTypes;
    $month = $data->month;
    $monthName = Carbon::parse($month)->translatedFormat('F Y');
    $allallowanceTypes = array_reverse($allallowanceTypes, true);
    $employeeAllowances = collect($increaseDetails)->map(function ($allowance) use ($allallowanceTypes) {
        return [
            'allowance_name' => $allallowanceTypes[$allowance['type_id']] ?? 'Unknown Allowance',
            'amount' => $allowance['amount'],
        ];
    });

    $employeeDeductions = collect($deducationDetails)->map(function ($deduction) {
        return [
            'deduction_name' => $deduction['deduction_name'] ?? 'Unknown Deduction',
            'deduction_amount' => $deduction['deduction_amount'],
        ];
    });

    $totalAllowanceAmount = $employeeAllowances->sum('amount') + ($data->details[0]['overtime_pay'] ?? 0) + ($employee->salary ?? 0) + ($data->details[0]['total_incentives'] ?? 0);
    $totalDeductionAmount = $employeeDeductions->sum('deduction_amount');

    $viewData = compact(
        'data',
        'totalAllowanceAmount',
        'totalDeductionAmount',
        'employeeAllowances',
        'employeeDeductions',
        'month',
        'monthName',
        'employee',
        'branch'
    );

    $pdf = Pdf::loadView('export.reports.hr.salaries.salary-slip', $viewData);
    return $pdf->output(); // Return PDF content
}


function generateMultipleSalarySlips(array $employeeIds, $sid)
{
    $pdfFiles = [];

    foreach ($employeeIds as $employeeId) {
        $employee = Employee::find($employeeId);
        $branch = $employee->branch;
        $data = employeeSalarySlip($employeeId, $sid);

        $month = $data->month;
        $monthName = Carbon::parse($month)->translatedFormat('F Y');

        $employeeAllowances = collect($data->increaseDetails)->map(function ($allowance) {
            return [
                'allowance_name' => $allowance['type_id'] ?? 'Unknown Allowance',
                'amount' => $allowance['amount'],
            ];
        })->toArray();

        $employeeDeductions = collect($data->deducationDetails)->map(function ($deduction) {
            return [
                'deduction_name' => $deduction['deduction_id'] ?? 'Unknown Deduction',
                'deduction_amount' => $deduction['deduction_amount'],
            ];
        })->toArray();

        $totalAllowanceAmount = collect($employeeAllowances)->sum('amount') + ($data->details[0]['overtime_pay'] ?? 0) + ($employee->salary ?? 0) + ($data->details[0]['total_incentives'] ?? 0);
        $totalDeductionAmount = collect($employeeDeductions)->sum('deduction_amount');

        // Prepare data for PDF
        $viewData = compact(
            'data',
            'totalAllowanceAmount',
            'totalDeductionAmount',
            'employeeAllowances',
            'employeeDeductions',
            'month',
            'monthName',
            'employee',
            'branch'
        );

        // Generate the PDF
        $pdf = Pdf::loadView('export.reports.hr.salaries.salary-slip', $viewData);

        // Save the PDF to a temporary location
        $fileName = "salary_slip_{$employee->name}.pdf";
        $filePath = "temp/{$fileName}";

        Storage::disk('public')->put($filePath, $pdf->output());

        $pdfFiles[] = [
            'name' => $fileName,
            'url' => Storage::url($filePath),
        ];
    }

    return $pdfFiles;
}

function generateBulkSalarySlipPdf(array $employeeIds, $sid)
{
    $mergedPdf = new Pdf; // For merging PDFs, consider libraries like `setasign/fpdi`.

    $zipFilePath = storage_path('app/public/salary_slips.zip'); // Temporary storage for ZIP file
    $zip = new \ZipArchive();

    if ($zip->open($zipFilePath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) === true) {
        foreach ($employeeIds as $employeeId) {
            $employee = Employee::find($employeeId);
            $branch = $employee->branch;
            $data = employeeSalarySlip($employeeId, $sid);

            $increaseDetails = $data->increaseDetails;
            $deducationDetails = $data->deducationDetails;
            $monthName = Carbon::parse($data->month)->translatedFormat('F Y');

            $employeeAllowances = collect($increaseDetails)->map(function ($allowance) {
                return [
                    'allowance_name' => $allowance['type_id'] ?? 'Unknown Allowance',
                    'amount' => $allowance['amount'],
                ];
            })->toArray();

            $employeeDeductions = collect($deducationDetails)->map(function ($deduction) {
                return [
                    'deduction_name' => $deduction['deduction_id'] ?? 'Unknown Deduction',
                    'deduction_amount' => $deduction['deduction_amount'],
                ];
            })->toArray();

            $totalAllowanceAmount = collect($employeeAllowances)->sum('amount');
            $totalDeductionAmount = collect($employeeDeductions)->sum('deduction_amount');

            $viewData = compact(
                'employee',
                'branch',
                'data',
                'employeeAllowances',
                'employeeDeductions',
                'totalAllowanceAmount',
                'totalDeductionAmount',
                'monthName'
            );

            $pdf = Pdf::loadView('export.reports.hr.salaries.salary-slip', $viewData);

            // Save individual PDF in the ZIP file
            $fileName = "salary_slip_{$employee->name}.pdf";
            $zip->addFromString($fileName, $pdf->output());
        }

        $zip->close();

        return response()->download($zipFilePath)->deleteFileAfterSend(true);
    } else {
        throw new \Exception('Failed to create ZIP file.');
    }
}


function sanitizeString($string)
{
    return mb_convert_encoding($string, 'UTF-8', 'UTF-8');
}
/**
 * to get installments monthly advanced
 */
function getInstallmentAdvancedMonthly($employee, $year, $month)
{

    // Check if the employee has an advance transaction for the specified month and year
    $advancedInstalmment = $employee?->transactions()
        ->where('transaction_type_id', 3)
        // ->whereYear('from_date', $year)
        // ->whereMonth('from_date', $month)
        ->with(['installments' => function ($query) use ($year, $month) {
            $query->whereYear('due_date', $year)
                ->whereMonth('due_date', $month)
                ->where('is_paid', false)
                ->limit(1); // Limit to only the first installment for efficiency
        }])
        ->first()?->installments->first();
    // dd($employee,$year,$month,$advancedInstalmment);

    return $advancedInstalmment;
}


if (!function_exists('calculateYearlyTax')) {
    /**
     * Calculate the yearly tax deduction for an employee.
     *
     * @param Employee $employee The employee instance.
     * @return float The yearly tax deduction amount.
     */
    function calculateYearlyTax(Employee $employee)
    {
        return 0;
        $tax = Deduction::where('has_brackets', 1)->whereHas('brackets')->first();
        return $tax->calculateTax($employee->salary)['monthly'] ?? 0;


        // // Check nationality (only for 'MY')
        // if ($employee->nationality !== 'MY') {
        //     return 0; // No tax for non-'MY' nationality
        // }
        if (!$employee->is_citizen) {
            return 0; // No tax for non-'MY' nationality
        }

        // Calculate yearly salary
        $yearlySalary = $employee->salary * 12;
        // $yearlySalary = $employee->salary ;

        // Use the tax brackets to determine the tax rate
        $taxPercentage = 0;
        foreach (Employee::TAX_BRACKETS as $bracket) {
            [$min, $max, $percentage] = $bracket;
            if ($yearlySalary >= $min && $yearlySalary <= $max) {
                $taxPercentage = $percentage;
                break;
            }
        }
        // dd($taxPercentage,$yearlySalary);

        // Calculate the yearly tax deduction
        $yearlyTaxDeduction = ($yearlySalary * ($taxPercentage / 100));
        // $yearlyTaxDeduction = ($yearlySalary * ($taxPercentage / 100));
        // dd('hi', $yearlyTaxDeduction, $yearlySalary, $taxPercentage);
        $monthlyTaxDeduction = $yearlyTaxDeduction / 12;
        return round($monthlyTaxDeduction, 2); // Return rounded value
        return round($yearlyTaxDeduction, 2); // Return rounded value
    }
}



function checkForMonthlyBalanceAndCreateToCancelAbsent($employee, $yearAndMonth, $totalAbsentDays, $monthlyLeaveBalance, $absentDates)
{
    $date = new \DateTime($yearAndMonth . '-01');
    $year = $date->format('Y');
    $month = $date->format('m');
    $totalDaysOfMonth = $date->format('t');
    $leaveTypeId = LeaveType::where('active', 1)->where('type', LeaveType::TYPE_WEEKLY)->where('balance_period', LeaveType::BALANCE_PERIOD_MONTHLY)->first()?->id;
    $leaveBalance = LeaveBalance::where('employee_id', $employee->id)
        ->where('year', $year)
        ->where('month', $month)
        ->where('leave_type_id', $leaveTypeId)
        ->first();
    // dd($year, $month, $totalDaysOfMonth, $totalAbsentDays, $monthlyLeaveBalance, $absentDates, $leaveBalance->balance - $monthlyLeaveBalance);
    DB::beginTransaction();
    try {
        //code...
        DB::commit();

        for ($i = 0; $i < $monthlyLeaveBalance; $i++) {
            // dd($absentDates[$i]);
            EmployeeApplicationV2::create([
                'employee_id' => $employee->id,
                'branch_id' => $employee->branch_id,
                'application_date' => now()->toDateString(),
                'status' => EmployeeApplicationV2::STATUS_APPROVED,
                'notes' => 'Auto generated',
                'application_type_id' => 1,
                'application_type_name' => EmployeeApplicationV2::APPLICATION_TYPE_NAMES[EmployeeApplicationV2::APPLICATION_TYPE_LEAVE_REQUEST],
                'created_by' => 1,
                'approved_by' => 1,
                'approved_at' => now(),
            ])->leaveRequest()->create([
                'application_type_id' => 1,
                'application_type_name' => EmployeeApplicationV2::APPLICATION_TYPE_NAMES[EmployeeApplicationV2::APPLICATION_TYPE_LEAVE_REQUEST],
                'employee_id' => $employee->id,
                'leave_type' => $leaveTypeId,
                'year' => $year,
                'month' => $month,
                'start_date' => $absentDates[$i],
                'end_date' => $absentDates[$i],
                'days_count' => 1,
            ])
            ;
        }
        $leaveBalance
            ->update([
                'balance' => $leaveBalance->balance - $monthlyLeaveBalance,
            ]);
        Log::alert('done_created_auto_monthly_leave', ['employee' => $employee, 'absentDates' => $absentDates]);
        return ['result' => true];
    } catch (\Throwable $th) {
        //throw $th;
        DB::rollBack();
        Log::error('failed_creating_auto_monthly', ['error' => $th]);
        return ['result' => false];
    }
}

function createAutoMonthlyLeave($createPayrol = false)
{
    if ($createPayrol) {
        return true;
    }
    return false;
}
function ____($employee, $yearAndMonth, $totalAbsentDays, $monthlyLeaveBalance, $absentDates)
{
    $date = new \DateTime($yearAndMonth . '-01');
    $year = $date->format('Y');
    $month = $date->format('m');
    $totalDaysOfMonth = $date->format('t');
    // dd($year, $month, $totalDaysOfMonth, $totalAbsentDays, $monthlyLeaveBalance, $absentDates);
    DB::beginTransaction();
    // try {
    //     //code...
    //     DB::commit();

    //     for ($i = 0; $i < $monthlyLeaveBalance; $i++) {
    //         // dd($absentDates[$i]);
    //         EmployeeApplicationV2::create([
    //             'employee_id' => $employee->id,
    //             'branch_id' => $employee->branch_id,
    //             'application_date' => now()->toDateString(),
    //             'status' => EmployeeApplicationV2::STATUS_APPROVED,
    //             'notes' => 'Auto generated',
    //             'application_type_id' => 1,
    //             'application_type_name' => EmployeeApplicationV2::APPLICATION_TYPE_NAMES[EmployeeApplicationV2::APPLICATION_TYPE_LEAVE_REQUEST],
    //             'created_by' => 1,
    //             'approved_by' => 1,
    //             'approved_at' => now(),
    //         ])->leaveRequest()->create([
    //             'application_type_id' => 1,
    //             'application_type_name' => EmployeeApplicationV2::APPLICATION_TYPE_NAMES[EmployeeApplicationV2::APPLICATION_TYPE_LEAVE_REQUEST],
    //             'employee_id' => $employee->id,
    //             'leave_type' => LeaveType::where('active', 1)->where('used_as_weekend', 1)->first()?->id,
    //             'year' => $year,
    //             'month' => $month,
    //             'start_date' => $absentDates[$i],
    //             'end_date' => $absentDates[$i],
    //             'days_count' => 1,
    //         ])
    //         ;
    //         LeaveBalance::create([
    //             'employee_id' => $employee->id,
    //             'leave_type_id' => LeaveType::where('active', 1)->where('used_as_weekend', 1)->first()?->id,
    //             'year' => $year,
    //             'month' => $month,
    //             'balance' => $monthlyLeaveBalance,
    //             'branch_id' => $employee->branch_id,
    //             'created_by' => 1,
    //         ]);
    //     }
    //     Log::alert('done_created_auto_monthly_leave', ['employee' => $employee, 'absentDates' => $absentDates]);
    // } catch (\Throwable $th) {
    //     //throw $th;
    //     DB::rollBack();
    //     Log::error('failed_creating_auto_monthly', ['error' => $th]);
    // }
    try {
        // Initialize arrays to hold the created records
        $createdEmployeeApplications = [];
        $createdLeaveRequests = [];
        $createdLeaveBalances = [];

        for ($i = 0; $i < $monthlyLeaveBalance; $i++) {
            // Create EmployeeApplicationV2 record and store it in the array
            $employeeApplication = EmployeeApplicationV2::make([
                'employee_id' => $employee->id,
                'branch_id' => $employee->branch_id,
                'application_date' => now()->toDateString(),
                'status' => EmployeeApplicationV2::STATUS_APPROVED,
                'notes' => 'Auto generated',
                'application_type_id' => 1,
                'application_type_name' => EmployeeApplicationV2::APPLICATION_TYPE_NAMES[EmployeeApplicationV2::APPLICATION_TYPE_LEAVE_REQUEST],
                'created_by' => 1,
                'approved_by' => 1,
                'approved_at' => now(),
            ]);

            // Create related leaveRequest and store it in the array
            $leaveType = LeaveType::where('active', 1)->where('type', LeaveType::TYPE_WEEKLY)->where('balance_period', LeaveType::BALANCE_PERIOD_MONTHLY)->first();
            $leaveRequest = $employeeApplication->leaveRequest()->make([
                'application_type_id' => 1,
                'application_type_name' => EmployeeApplicationV2::APPLICATION_TYPE_NAMES[EmployeeApplicationV2::APPLICATION_TYPE_LEAVE_REQUEST],
                'employee_id' => $employee->id,
                'leave_type' => $leaveType?->id,
                'year' => $year,
                'month' => $month,
                'start_date' => $absentDates[$i],
                'end_date' => $absentDates[$i],
                'days_count' => 1,
            ]);

            // Add the leave request and employee application to the respective arrays
            $createdEmployeeApplications[] = $employeeApplication;
            $createdLeaveRequests[] = $leaveRequest;

            // Create LeaveBalance record and store it in the array
            $leaveBalance = LeaveBalance::make([
                'employee_id' => $employee->id,
                'leave_type_id' => $leaveType?->id,
                'year' => $year,
                'month' => $month,
                'balance' => $monthlyLeaveBalance,
                'branch_id' => $employee->branch_id,
                'created_by' => 1,
            ]);
            $createdLeaveBalances[] = $leaveBalance;
        }

        // Once the loop is complete, return the created records
        DB::commit();

        return [
            'employeeApplications' => $createdEmployeeApplications,
            'leaveRequests' => $createdLeaveRequests,
            'leaveBalances' => $createdLeaveBalances,
        ];
    } catch (\Throwable $th) {
        DB::rollBack();
        Log::error('failed_creating_auto_monthly', ['error' => $th]);
        return ['error' => $th->getMessage()];
    }
}
