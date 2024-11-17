<?php

use App\Models\Allowance;
use App\Models\Deduction;
use App\Models\Employee;
use App\Models\MonthSalary;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;

/**
 * to calculate the salary
 */
function calculateMonthlySalaryV2($employeeId, $date)
{
    // Retrieve the employee model with relations to deductions, allowances, and incentives
    $employee = Employee::with(['deductions', 'allowances', 'monthlyIncentives'])
        ->whereNotNull('salary')
        ->find($employeeId)
    ;
    if (!$employee) {
        return 'Employee not found!';
    }

    $generalAllowanceTypes = Allowance::where('is_specific', 0)->where('active', 1)->select('name', 'is_percentage', 'amount', 'percentage', 'id')->get()->toArray();
    $generalDeducationTypes = Deduction::where('is_specific', 0)->where('active', 1)->select('name', 'is_percentage', 'amount', 'percentage', 'id')->get()->toArray();

    // Basic salary from the employee model
    $basicSalary = $employee->salary;

    $generalAllowanceResultCalculated = calculateAllowances($generalAllowanceTypes, $basicSalary);
    $generalDedeucationResultCalculated = calculateDeductions($generalDeducationTypes, $basicSalary);
    // return $generalDedeucationResultCalculated;
    // Calculate total deductions
    $specificDeductions = $employee->deductions->map(function ($deduction) {
        return [
            'is_percentage' => $deduction->is_percentage,
            'amount' => $deduction->amount,
            'percentage' => $deduction->percentage,
            'id' => $deduction->deduction_id,
            'name' => $deduction->deduction->name,
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
    $hourlySalary = calculateHourlySalary($employeeId, $date);

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
    if (!$employee->discount_exception_if_absent) {
        $totalAbsentDays = calculateTotalAbsentDays($attendances);
    }

    $totalLateHours = 0;
    if (!$employee->discount_exception_if_attendance_late) {
        $totalLateHours = calculateTotalLateArrival($attendances)['totalHoursFloat'];
    }

    $overtimeHours = getEmployeeOvertimes($date, $employee);
    // Calculate overtime pay (overtime hours paid at double the regular hourly rate)
    $overtimePay = $overtimeHours * $hourlySalary * setting('overtime_hour_multiplier');

    // Calculate net salary including overtime
    // $netSalary = $basicSalary + $totalAllowances + $totalMonthlyIncentives + $overtimePay - $totalDeductions;

    // Calculate deductions for absences and lateness
    $deductionForAbsentDays = $totalAbsentDays * $dailySalary; // Deduction for absent days
    $deductionForLateHours = $totalLateHours * $hourlySalary; // Deduction for late hours

    $totalDeducations = ($specificDeducationCalculated['result'] + $generalDedeucationResultCalculated['result'] + $deductionForLateHours + $deductionForAbsentDays + ($deducationInstallmentAdvancedMonthly?->installment_amount ?? 0));
    $totalAllowances = ($specificAlloanceCalculated['result'] + $generalAllowanceResultCalculated['result']);
    $totalOtherAdding = ($overtimePay + $totalMonthlyIncentives);

    $netSalary = ($basicSalary + $totalAllowances + $totalOtherAdding) - $totalDeducations;
    $remaningSalary = round($netSalary - round($totalDeducations, 2), 2);
    $netSalary = replaceZeroInstedNegative($netSalary);
    // Return the details and net salary breakdown
    return [
        'net_salary' => round($netSalary, 2),
        'details' => [
            'basic_salary' => ($basicSalary),
            'salary_after_deducation' => replaceZeroInstedNegative($remaningSalary),
            'deducationInstallmentAdvancedMonthly' => $deducationInstallmentAdvancedMonthly?->installment_amount,
            'total_deducation' => round($totalDeducations, 2),
            'total_allowances' => round($totalAllowances, 2),
            'total_other_adding' => round($totalOtherAdding, 2),
            'specific_deducation_result' => round($specificDeducationCalculated['result'], 2),
            'specific_allowances_result' => round($specificAlloanceCalculated['result'], 2),
            'general_deducation_result' => round($generalDedeucationResultCalculated['result'], 2),
            'general_allowances_result' => round($generalAllowanceResultCalculated['result'], 2),
            'deduction_for_absent_days' => round($deductionForAbsentDays, 2),
            'deduction_for_late_hours' => round($deductionForLateHours, 2),
            'total_monthly_incentives' => $totalMonthlyIncentives,
            'overtime_pay' => round($overtimePay, 2),
            'overtime_hours' => $overtimeHours,
            'total_absent_days' => $totalAbsentDays,
            'total_late_hours' => $totalLateHours,
            'deducation_details' => [
                'specific_deducation' => $specificDeducationCalculated,
                'general_deducation' => $generalDedeucationResultCalculated,
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
function calculateHourlySalary($employeeId, $date = null)
{
    // Get the daily salary using the given date
    $dailySalary = calculateDailySalary($employeeId, $date);

    if (!is_numeric($dailySalary)) {
        return $dailySalary; // Return error message from calculateDailySalary if any
    }

    // Calculate hourly salary assuming an 8-hour workday
    $hourlySalary = $dailySalary / setting('hours_no_in_day');

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

function testPdfDownload()
{
    // Sample Data
    $employee = (object) [
        'name' => 'John Doe',
        'employee_no' => 'EMP12345',
        'job_title' => 'Software Engineer',
        'branch' => (object) ['name' => 'Main Branch'],
    ];

    $monthName = 'October 2024';

    $employeeAllowances = [
        ['allowance_name' => 'Transport Allowance', 'amount' => 200.50],
        ['allowance_name' => 'Housing Allowance', 'amount' => 500.00],
    ];

    $employeeDeductions = [
        ['deduction_name' => 'Tax', 'deduction_amount' => 150.00],
        ['deduction_name' => 'Insurance', 'deduction_amount' => 50.00],
    ];

    $data = [
        'details' => [
            [
                'overtime_pay' => 100.00,
                'total_incentives' => 50.00,
                'net_salary' => 1600.50,
            ],
        ],
    ];

    $totalAllowanceAmount = collect($employeeAllowances)->sum('amount');
    $totalDeductionAmount = collect($employeeDeductions)->sum('deduction_amount');

    // Generate PDF
    $pdf = Pdf::loadView('export.reports.hr.salaries.salary-slip-test-pdf', compact(
        'employee',
        'monthName',
        'employeeAllowances',
        'employeeDeductions',
        'data',
        'totalAllowanceAmount',
        'totalDeductionAmount'
    ));

    // Return PDF
    return $pdf->download('salary-slip.pdf');
}


function convert_to_utf8_recursively($dat){
    if( is_string($dat) ){
        return mb_convert_encoding($dat, 'UTF-8', 'UTF-8');
    }
    elseif( is_array($dat) ){
        $ret = [];
        foreach($dat as $i => $d){
            $ret[$i] = convert_to_utf8_recursively($d);
        }
        return $ret;
    }
    else{
        return $dat;
    }
}

function generateSalarySlipPdf($employeeId, $sid)
{
    // Fetch the month salary with related details
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
    ])->findOrFail($sid);

    // Retrieve employee and branch information
    $employeeName = sanitizeString($employee->name ?? 'Unknown');
    $branchName = sanitizeString($branch->name ?? 'Unknown');

    $employee = Employee::find($employeeId);
    $branch = $employee->branch;
    // $data = employeeSalarySlip($empId,$sid);
    // Set the month name dynamically from monthSalary
    $monthName = $monthSalary->month;

    // Calculate total allowances and deductions
    $employeeAllowances = $monthSalary->increaseDetails->map(function ($detail) {
        return [
            'allowance_name' => $detail->allowance_name,
            'amount' => $detail->amount,
        ];
    });

    $employeeDeductions = $monthSalary->deducationDetails->map(function ($detail) {
        return [
            'deduction_name' => $detail->deduction_name,
            'deduction_amount' => $detail->amount,
        ];
    });

    $totalAllowanceAmount = $employeeAllowances->sum('amount');
    $totalDeductionAmount = $employeeDeductions->sum('deduction_amount');

    // Prepare the data for the view
    $data = $monthSalary;

    try {
        // Prepare Data for the Blade View
        $viewData = compact(
            'employee',
            'branch',
            'monthName',
            'employeeAllowances',
            'employeeDeductions',
            'data',
            'totalAllowanceAmount',
            'totalDeductionAmount'
        );

// dd($viewData);
        // dd($d);
        $pdf = Pdf::loadView(
            'export.reports.hr.salaries.salary-slip', convert_to_utf8_recursively($viewData)
        );
        return $pdf->stream('salary_slip.pdf');
    } catch (\Exception $e) {
        dd($e->getMessage());
    }
    // Return the generated PDF
    // return $pdf->stream('salary_slip.pdf'); // Display the PDF in the browser
    // return $pdf->download('salary_slip.pdf'); // Uncomment to download the PDF
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
        ->whereYear('from_date', $year)
        ->whereMonth('from_date', $month)
        ->with(['installments' => function ($query) use ($year, $month) {
            $query->whereYear('due_date', $year)
                ->whereMonth('due_date', $month)
                ->where('is_paid', false)
                ->limit(1); // Limit to only the first installment for efficiency
        }])
        ->first()?->installments->first();

    return $advancedInstalmment;
}
