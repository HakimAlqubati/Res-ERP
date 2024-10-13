<?php

use App\Models\Allowance;
use App\Models\Attendance;
use App\Models\Deduction;
use App\Models\Employee;
use App\Models\Holiday;
use App\Models\MonthSalaryDetail;
use App\Models\Order;
use App\Models\Store;
use App\Models\SystemSetting;
use App\Models\UnitPrice;
use App\Models\User;
use App\Models\UserType;
use App\Models\WeeklyHoliday;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
function getName()
{
    return 'Eng. Hakeem';
}

/**
 * to format money
 */
function formatMoney($amount, $currency = '$')
{
    return $currency . ' ' . number_format($amount, 2);
}

/**
 * to return current role
 */
function getCurrentRole()
{
    $roleId = 0;
    if (isset(auth()?->user()?->roles) && count(auth()?->user()?->roles) > 0) {
        $roleId = auth()?->user()?->roles[0]?->id;
    }
    return $roleId;
}

/**
 * to check if current user is super admin
 */

function isSuperAdmin()
{
    $currentRole = getCurrentRole();
    if ($currentRole == 1) {
        return true;
    }
    return false;
}
/**
 * to get branch id
 */
function getBranchId()
{
    return auth()->user()?->branch?->id;
}

/**
 * to add filament request select
 */
function __filament_request_select($key, $default = null)
{
    if (request()->isMethod('post')) {
        $qu = request()->all();
        $data = data_get($qu, "serverMemo.data.tableFilters." . $key . ".value");

        if (data_get($qu, "updates.0.payload.name") == "tableFilters.$key.value") {
            $data = data_get($qu, "updates.0.payload.value", $data);
        }

        if (data_get($qu, "updates.0.payload.params.0") == "tableFilters.$key.value") {
            $data = data_get($qu, "updates.0.payload.params.1", $data);
        }

        if (is_array($data)) {
            return $default;
        }

        return $data ?? $default;
    } else {
        $qu = request()->query();
        $data = data_get($qu, "tableFilters." . $key . ".value", $default);
        if (is_array($data)) {
            return $default;
        }
        return $data;
    }
}

function __filament_request_select_multiple($key, $default = null, $multiple = false, $type = null)
{
    if (!empty($type)) {
        $valueType = $type;
        $multiple = true;
    } else {
        $valueType = $multiple ? 'values' : 'value';
    }

    if (request()->isMethod('post')) {
        $qu = request()->all();
        $data = data_get_recursive($qu, "serverMemo.data.tableFilters." . $key . ".$valueType");

        if (data_get_recursive($qu, "updates.0.payload.name") == "tableFilters.$key.$valueType") {
            $data = data_get_recursive($qu, "updates.0.payload.$valueType", $data);
        }

        if (data_get_recursive($qu, "updates.0.payload.params.0") == "tableFilters.$key.$valueType") {
            $data = data_get_recursive($qu, "updates.0.payload.params.1", $data);
        }

        if ($multiple) {
            return is_array($data) ? $data : $default;
        }

        return $data ?? $default;
    } else {
        $qu = request()->query();
        $data = data_get($qu, "tableFilters." . $key . ".$valueType", $default);
        if (is_array($data) && !$multiple) {
            return $default;
        }
        return $data;
    }
}

function data_get_recursive($target, $key, $default = null)
{
    if (is_null($key)) {
        return $target;
    }

    $key = is_array($key) ? $key : explode('.', $key);

    foreach ($key as $i => $segment) {
        unset($key[$i]);

        if (is_null($segment)) {
            return $target;
        }

        if ($segment === '*') {
            if ($target instanceof Collection) {
                $target = $target->all();
            } elseif (!is_array($target)) {
                return value($default);
            }

            $result = [];

            foreach ($target as $item) {
                $value = data_get_recursive($item, $key);

                if (is_array($value)) {
                    $result = array_merge($result, $value);
                } else {
                    $result[] = $value;
                }
            }

            return in_array('*', $key) ? Arr::collapse($result) : $result;
        }

        if (Arr::accessible($target) && Arr::exists($target, $segment)) {
            $target = $target[$segment];
        } elseif (is_object($target) && isset($target->{$segment})) {
            $target = $target->{$segment};
        } else {
            return value($default);
        }
    }

    return $target;
}

/**
 * to add filament request date filter
 */
function __filament_request_key($key, $default = null)
{
    if (request()->isMethod('post')) {
        $qu = request()->all();
        $data = data_get($qu, "serverMemo.data.tableFilters." . $key);

        if (data_get($qu, "updates.0.payload.params.0") == "tableFilters.$key") {
            $data = data_get($qu, "updates.0.payload.params.1", $data);
        }

        if (is_array($data)) {
            return $default;
        }

        return $data ?? $default;
    } else {
        $qu = request()->query();
        $data = data_get($qu, "tableFilters." . $key, $default);

        if (is_array($data)) {
            return $default;
        }
        return $data;
    }
}

/**
 * get admins to notify [Super admin - Manager] roles
 */

function getAdminsToNotify()
{
    $adminIds = [];
    $adminIds = User::whereHas("roles", function ($q) {
        $q->whereIn("id", [1, 3]);
    })->select('id', 'name')->get()->pluck('id')->toArray();
    $recipients = User::whereIn('id', $adminIds)->get(['id', 'name']);
    return $recipients;
}

/**
 * get default store
 */
function getDefaultStore()
{
    $defaultStoreId = Store::where('default_store', 1)->where('active', 1)->select('id')->first()?->id;
    if (is_null($defaultStoreId)) {
        $defaultStoreId = 0;
    }
    return $defaultStoreId;
}

/**
 * to get default currency
 */
function getDefaultCurrency()
{
    $defaultCurrency = 'RM';
    $systemSettingsCurrency = SystemSetting::select('currency_symbol')->first();
    if ($systemSettingsCurrency) {
        $defaultCurrency = $systemSettingsCurrency->currency_symbol;
    }
    return $defaultCurrency;
}

/**
 * to get method of calculating prices of orders
 */
function getCalculatingPriceOfOrdersMethod()
{
    $defaultMethod = 'from_unit_prices';

    $systemSettingsCalculatingMethod = SystemSetting::select('calculating_orders_price_method')->first()->calculating_orders_price_method;

    if ($systemSettingsCalculatingMethod != null && ($systemSettingsCalculatingMethod != $defaultMethod)) {
        $defaultMethod = $systemSettingsCalculatingMethod;
    }
    return $defaultMethod;
}

/**
 * get price from unit price by product_id & unit_id
 */
function getUnitPrice($product_id, $unit_id)
{
    return UnitPrice::where(
        'product_id',
        $product_id
    )->where('unit_id', $unit_id)?->first()?->price;
}

/**
 * function to check if user has pending approval order when submit order
 */
function checkIfUserHasPendingForApprovalOrder($branchId)
{
    $order = Order::where('status', Order::PENDING_APPROVAL)
        ->where('branch_id', $branchId)
        ->where('active', 1)
        ->first();

    return $order ? $order->id : null;
}

/**
 * function to return no last days to return orders in mobile
 */
function getLimitDaysOrders()
{
    $limitDays = SystemSetting::select('limit_days_orders')?->first()?->limit_days_orders;
    if ($limitDays) {
        return $limitDays;
    }
    return 30; // 30 days as default
}

/**
 * function to return default user orders status
 */
function getEnableUserOrdersToStore()
{
    return SystemSetting::select('enable_user_orders_to_store')?->first()?->enable_user_orders_to_store;
}

/**
 * to return days as static with array
 */
function getDays()
{
    return [
        'Monday' => 'Monday',
        'Tuesday' => 'Tuesday',
        'Wednesday' => 'Wednesday',
        'Thursday' => 'Thursday',
        'Friday' => 'Friday',
        'Saturday' => 'Saturday',
        'Sunday' => 'Sunday',
    ];
}

/**
 * to get user types as pluck(name,id)
 */
function getUserTypes()
{
    return UserType::select('name', 'id')->get()->pluck('name', 'id');
}
/**
 * to get roles based on user_type_id
 */
function getRolesByTypeId($id)
{
    $user_types = UserType::find($id)?->role_ids;
    return $user_types;

}

/**
 * to calculate the salary
 */

function calculateMonthlySalaryV2($employeeId, $date)
{
    // Retrieve the employee model with relations to deductions, allowances, and incentives
    $employee = Employee::with(['deductions', 'allowances', 'monthlyIncentives'])->find($employeeId);
    if (!$employee) {
        return 'Employee not found!';
    }

    $generalAllowanceTypes = Allowance::where('is_specific', 0)->where('active', 1)->select('name', 'is_percentage', 'amount', 'percentage', 'id')->get()->toArray();
    $generalDeducationTypes = Deduction::where('is_specific', 0)->where('active', 1)->select('name', 'is_percentage', 'amount', 'percentage', 'id')->get()->toArray();

    // Basic salary from the employee model
    $basicSalary = $employee->salary;

    $generalAllowanceResultCalculated = calculateAllowances($generalAllowanceTypes, $basicSalary);
    $generalDedeucationResultCalculated = calculateDeductions($generalDeducationTypes, $basicSalary);
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
    $totalAbsentDays = calculateTotalAbsentDays($attendances);
    $totalLateHours = calculateTotalLateArrival($attendances)['totalHoursFloat'];

    $overtimeHours = getEmployeeOvertimes($date, $employee);
    // Calculate overtime pay (overtime hours paid at double the regular hourly rate)
    $overtimePay = $overtimeHours * $hourlySalary * 2;

    // Calculate net salary including overtime
    // $netSalary = $basicSalary + $totalAllowances + $totalMonthlyIncentives + $overtimePay - $totalDeductions;

    // Calculate deductions for absences and lateness
    $deductionForAbsentDays = $totalAbsentDays * $dailySalary; // Deduction for absent days
    $deductionForLateHours = $totalLateHours * $hourlySalary; // Deduction for late hours

    $totalDeducations = ($specificDeducationCalculated['result'] + $generalDedeucationResultCalculated['result'] + $deductionForLateHours + $deductionForAbsentDays);
    $totalAllowances = ($specificAlloanceCalculated['result'] + $generalAllowanceResultCalculated['result']);
    $totalOtherAdding = ($overtimePay + $totalMonthlyIncentives);

    $netSalary = ($basicSalary + $totalAllowances + $totalOtherAdding) - $totalDeducations;

    // Return the details and net salary breakdown
    return [
        'net_salary' => round($netSalary, 2),
        'details' => [
            'basic_salary' => $basicSalary,
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
            'overtime_pay' => $overtimePay,
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
    $hourlySalary = $dailySalary / 8;

    return round($hourlySalary, 2);
}

/**
 * to calculate days in month
 */
function getDaysInMonth($date)
{
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
    $overtimesForMonth = $employee->overtimes->filter(function ($overtime) use ($month) {
        return \Carbon\Carbon::parse($overtime->date)->month == $month;
    });

    $totalHours = $overtimesForMonth->sum(function ($overtime) {
        return (float) $overtime->hours; // Ensure the 'hours' value is cast to float
    });

    return $totalHours;
}

/**
 * to get employee periods
 */
function searchEmployeePeriod($employee, $time, $day, $checkType)
{
    // Decode the days array for each period
    $workTimePeriods = $employee->periods->map(function ($period) {
        $period->days = json_decode($period->days); // Ensure days are decoded
        return $period;
    });

    // Filter periods by the day
    $periodsForDay = $workTimePeriods->filter(function ($period) use ($day) {
        return in_array($day, $period->days);
    });

    // Check if no periods are found for the given day
    if ($periodsForDay->isEmpty()) {
        return "There is no period for today.";
    }

    // Convert the input time to a Carbon instance
    $checkTime = \Carbon\Carbon::createFromFormat('H:i:s', $time);

    // Sort periods based on proximity to check-in or check-out
    $nearestPeriod = $periodsForDay->sortBy(function ($period) use ($checkTime, $checkType) {
        $startTime = \Carbon\Carbon::createFromFormat('H:i:s', $period->start_at);
        $endTime = \Carbon\Carbon::createFromFormat('H:i:s', $period->end_at);

        if ($checkType == Attendance::CHECKTYPE_CHECKIN) {
            // For check-in, find the nearest start time
            return abs($checkTime->diffInMinutes($startTime, false));
        } elseif ($checkType == Attendance::CHECKTYPE_CHECKOUT) {
            // For check-out, find the nearest end time
            return abs($checkTime->diffInMinutes($endTime, false));
        }

        return PHP_INT_MAX;
    })->first();

    // If no period is found based on proximity
    if (!$nearestPeriod) {
        return "There is no period that matches your check-in/check-out time.";
    }

    // Determine whether the check is early or late, and calculate the number of minutes
    $startTime = \Carbon\Carbon::createFromFormat('H:i:s', $nearestPeriod->start_at);
    $endTime = \Carbon\Carbon::createFromFormat('H:i:s', $nearestPeriod->end_at);

    $minutesDifference = 0;
    $checkStatus = '';

    if ($checkType == 'checkin') {
        // Check if it's an early or late check-in
        $minutesDifference = $checkTime->diffInMinutes($startTime, false);
        $checkStatus = $minutesDifference < 0 ? 'early' : 'late';
        $minutesDifference = abs($minutesDifference); // Get the absolute number of minutes
    } elseif ($checkType == 'checkout') {
        // Check if it's an early or late check-out
        $minutesDifference = $checkTime->diffInMinutes($endTime, false);
        $checkStatus = $minutesDifference > 0 ? 'late' : 'early';
        $minutesDifference = abs($minutesDifference); // Get the absolute number of minutes
    }

    // Return the data in the requested format
    return [
        'id' => $nearestPeriod->id,
        'name' => $nearestPeriod->name,
        'start_at' => $nearestPeriod->start_at,
        'end_at' => $nearestPeriod->end_at,
        'allowed_count_minutes_late' => $nearestPeriod->allowed_count_minutes_late,
        'check_type' => $checkType,
        'status' => $checkStatus, // Whether the check-in/out was early or late
        'minutes_difference' => $minutesDifference, // How many minutes early or late
    ];
}

/**
 * to attendance employee
 */
// function attendanceEmployee($employee, $time, $day, $checkType,$checkDate)
function attendanceEmployee($employee, $time, $day, $checkType, $checkDate)
{
    // Decode the days array for each period
    $workTimePeriods = $employee->periods->map(function ($period) {
        $period->days = json_decode($period->days); // Ensure days are decoded
        return $period;
    });

    // Filter periods by the day
    $periodsForDay = $workTimePeriods->filter(function ($period) use ($day) {
        return in_array($day, $period->days);
    });

    // Check if no periods are found for the given day
    if ($periodsForDay->isEmpty()) {
        return "There is no period for today.";
    }

    // Convert the input time to a Carbon instance
    $checkTime = \Carbon\Carbon::createFromFormat('H:i:s', $time);

    // Sort periods based on proximity to check-in or check-out
    $nearestPeriod = $periodsForDay->sortBy(function ($period) use ($checkTime, $checkType) {
        $startTime = \Carbon\Carbon::createFromFormat('H:i:s', $period->start_at);
        $endTime = \Carbon\Carbon::createFromFormat('H:i:s', $period->end_at);

        if ($checkType == Attendance::CHECKTYPE_CHECKIN) {
            // For check-in, find the nearest start time
            return abs($checkTime->diffInMinutes($startTime, false));
        } elseif ($checkType == Attendance::CHECKTYPE_CHECKOUT) {
            // For check-out, find the nearest end time
            return abs($checkTime->diffInMinutes($endTime, false));
        }

        return PHP_INT_MAX;
    })->first();

    // If no period is found based on proximity
    if (!$nearestPeriod) {
        return "There is no period that matches your check-in/check-out time.";
    }

    // Prepare data array
    $data = [];
    $data['period_id'] = $nearestPeriod->id;
    $allowedLateMinutes = $nearestPeriod?->allowed_count_minutes_late;
    $startTime = \Carbon\Carbon::parse($nearestPeriod->start_at);
    $endTime = \Carbon\Carbon::parse($nearestPeriod->end_at);

    // Handle check-in scenario
    if ($checkType == Attendance::CHECKTYPE_CHECKIN) {
        if ($checkTime->gt($startTime)) {
            // Employee is late
            $data['delay_minutes'] = $startTime->diffInMinutes($checkTime);
            $data['early_arrival_minutes'] = 0;
            if ($allowedLateMinutes > 0) {
                $data['status'] = ($data['delay_minutes'] <= $allowedLateMinutes) ? Attendance::STATUS_ON_TIME : Attendance::STATUS_LATE_ARRIVAL;
            } else {
                $data['status'] = Attendance::STATUS_LATE_ARRIVAL;
            }
        } else {
            // Employee is early
            $data['delay_minutes'] = 0;
            $data['early_arrival_minutes'] = $checkTime->diffInMinutes($startTime);
            $data['status'] = ($data['early_arrival_minutes'] == 0) ? Attendance::STATUS_ON_TIME : Attendance::STATUS_EARLY_ARRIVAL;
        }
        $data['late_departure_minutes'] = 0; // Initialize for check-in
    }

    // Handle check-out scenario
    elseif ($checkType == Attendance::CHECKTYPE_CHECKOUT) {
        // Find the corresponding check-in record
        $checkinRecord = Attendance::where('employee_id', $employee->id)
            ->where('period_id', $data['period_id'])
            ->where('check_type', Attendance::CHECKTYPE_CHECKIN)
            ->whereDate('check_date', $checkDate) // Use the provided check date
            ->first();

        if ($checkinRecord) {
            $checkinTime = \Carbon\Carbon::parse($checkinRecord->check_time);

            // Calculate the actual duration (from check-in to check-out)
            $actualDuration = $checkinTime->diff($checkTime);
            $hoursActual = $actualDuration->h;
            $minutesActual = $actualDuration->i;

            // Calculate the supposed duration (from period start to end)
            $supposedDuration = $startTime->diff($endTime);
            $hoursSupposed = $supposedDuration->h;
            $minutesSupposed = $supposedDuration->i;

            // Store both durations in a format like "hours:minutes"
            $data['actual_duration_hourly'] = sprintf('%02d:%02d', $hoursActual, $minutesActual);
            $data['supposed_duration_hourly'] = sprintf('%02d:%02d', $hoursSupposed, $minutesSupposed);
        }

        // Calculate late departure or early departure
        if ($checkTime->gt($endTime)) {
            // Late departure
            $data['late_departure_minutes'] = $endTime->diffInMinutes($checkTime);
            $data['early_departure_minutes'] = 0;
            $data['status'] = Attendance::STATUS_LATE_DEPARTURE;
        } else {
            // Early departure
            $data['late_departure_minutes'] = 0;
            $data['early_departure_minutes'] = $checkTime->diffInMinutes($endTime);
            $data['status'] = Attendance::STATUS_EARLY_DEPARTURE;
        }
        $data['delay_minutes'] = 0; // Initialize for check-out
    }

    // Handle case where no matching period is found
    else {
        $data['period_id'] = 0;
        $data['delay_minutes'] = 0;
        $data['early_arrival_minutes'] = 0;
        $data['late_departure_minutes'] = 0;
        $data['early_departure_minutes'] = 0;
    }

    // Return the processed data
    return $data;
}

/**
 * to get employee attendances
 */

function employeeAttendances($employeeId, $startDate, $endDate)
{
    // Convert the dates to Carbon instances for easier manipulation
    $startDate = Carbon::parse($startDate);
    $endDate = Carbon::parse($endDate);

    // Get weekend days from the WeeklyHoliday model
    $weekend_days = json_decode(WeeklyHoliday::select('days')?->first()?->days);

    // Fetch holidays within the date range
    $holidays = Holiday::where('active', 1)
        ->whereBetween('from_date', [$startDate, $endDate])
        ->orWhereBetween('to_date', [$startDate, $endDate])
        ->select('from_date', 'to_date', 'count_days', 'name')
        ->get()
        ->keyBy('from_date');

    // Fetch leave applications within the date range
    $employee = Employee::find($employeeId);

    $leaveApplications = $employee?->approvedLeaveApplications()
        ->where(function ($query) use ($startDate, $endDate) {
            $query->whereBetween('from_date', [$startDate, $endDate])
                ->orWhereBetween('to_date', [$startDate, $endDate]);
        })
        ->select('from_date', 'to_date', 'leave_type_id')
        ->with('leaveType:id,name') // Assuming you have a relationship defined
        ->get();

    // Initialize an array to hold the results
    $result = [];

    // Loop through each date in the date range
    for ($date = $startDate->copy(); $date->lessThanOrEqualTo($endDate); $date->addDay()) {
        // Check if the current date is a weekend
        
        if ($weekend_days != null && in_array($date->format('l'), $weekend_days)) {
            continue; // Skip weekends
        }

        // Initialize the result for the current date
        $result[$date->toDateString()] = [
            'date' => $date->toDateString(),
            'day' => $date->format('l'),
            'periods' => [],
        ];

        // Check if the current date is a holiday
        if ($holidays->has($date->toDateString())) {
            $holiday = $holidays->get($date->toDateString());
            $result[$date->toDateString()]['holiday'] = [
                'name' => $holiday->name,
            ];
            continue; // Skip to the next date if it's a holiday
        }

        // Check if the current date falls within any leave applications
        if ($leaveApplications) {
            foreach ($leaveApplications as $leave) {
                if ($date->isBetween($leave->from_date, $leave->to_date, true)) {
                    $result[$date->toDateString()]['leave'] = [
                        'leave_type_id' => $leave->leave_type_id,
                        'leave_type_name' => $leave->leaveType->name ?? 'Unknown', // Include leave type name
                    ];
                    continue 2; // Skip to the next date if it's a leave day
                }
            }
        }

        // Get the employee periods for the current date
        $employeePeriods = DB::table('hr_work_periods as wp')
            ->join('hr_employee_periods as ep', 'wp.id', '=', 'ep.period_id')
            ->select('wp.start_at', 'wp.end_at', 'ep.period_id', 'ep.period_id')
            ->where('ep.employee_id', $employeeId)
            ->orderBy('wp.start_at', 'asc')
            ->get();

        if (count($employeePeriods) == 0) {
            $result[$date->toDateString()]['no_periods'] = true;
        }
        // Loop through each period
        foreach ($employeePeriods as $period) {
            // Get attendances for the current period and date
            $attendances = DB::table('hr_attendances as a')
                ->where('a.employee_id', '=', $employeeId)
                ->whereDate('a.check_date', '=', $date)
                ->where('a.period_id', '=', $period->period_id)
                ->select('a.*') // Adjust selection as needed
                ->get();

            // Structure for the current period
            $periodData = [
                'start_at' => $period->start_at,
                'end_at' => $period->end_at,
                'period_id' => $period->period_id,
                'attendances' => [],
            ];

            // Group attendances by check_type
            if ($attendances->isNotEmpty()) {
                $firstCheckin = null; // Variable to store the first check-in
                $lastCheckout = null; // Variable to store the last check-out
                $totalActualDuration = 0;
                $totalActualDurationMinutes = 0;
                foreach ($attendances as $attendance) {
                    if ($attendance->check_type === 'checkin') {
                        if ($firstCheckin === null) {
                            $firstCheckin = [
                                'check_time' => $attendance->check_time ?? null, // Include check_time
                                'early_arrival_minutes' => $attendance->early_arrival_minutes ?? 0,
                                'delay_minutes' => $attendance->delay_minutes ?? 0,
                                'status' => $attendance->status ?? 'unknown',
                            ];
                        }
                        $periodData['attendances']['checkin']['firstcheckout'] = $firstCheckin;
                        $periodData['attendances']['checkin'][] = [
                            'check_time' => $attendance->check_time ?? null, // Include check_time
                            'early_arrival_minutes' => $attendance->early_arrival_minutes ?? 0,
                            'delay_minutes' => $attendance->delay_minutes ?? 0,
                            'status' => $attendance->status ?? 'unknown',
                        ];

                    } elseif ($attendance->check_type === 'checkout') {

                        $lastCheckout = [
                            'check_time' => $attendance->check_time ?? null, // Include check_time
                            'status' => $attendance->status ?? 'unknown',
                            'actual_duration_hourly' => $attendance->actual_duration_hourly ?? 0,
                            'supposed_duration_hourly' => $attendance->supposed_duration_hourly ?? 0,
                            'early_departure_minutes' => $attendance->early_departure_minutes ?? 0,
                            'late_departure_minutes' => $attendance->late_departure_minutes ?? 0,
                            'total_actual_duration_hourly' => $attendance->total_actual_duration_hourly,
                        ];
                        $periodData['attendances']['checkout'][] = [
                            'check_time' => $attendance->check_time ?? null, // Include check_time
                            'status' => $attendance->status ?? 'unknown',
                            'actual_duration_hourly' => $attendance->actual_duration_hourly ?? 0,
                            'supposed_duration_hourly' => $attendance->supposed_duration_hourly ?? 0,
                            'early_departure_minutes' => $attendance->early_departure_minutes ?? 0,
                            'late_departure_minutes' => $attendance->late_departure_minutes ?? 0,
                            'total_actual_duration_hourly' => $attendance->total_actual_duration_hourly,
                        ];

                        $periodData['attendances']['checkout']['lastcheckout'] = $lastCheckout;
                    }
                }
            } else {
                // If there are no attendances, mark as 'absent'
                $periodData['attendances'] = 'absent';
            }

            // Add the period data to the result for the current date
            $result[$date->toDateString()]['periods'][] = $periodData;
        }
    }
    if (count($employeePeriods) == 0) {
        return 'no_periods';
    }
    return $result;
}

/**
 * to get employee attendace details of period []
 */
function getEmployeePeriodAttendnaceDetails($employeeId, $periodId, $date)
{
    // dd($employeeId,$periodId);
    $attenance = Attendance::where('employee_id', $employeeId)
        ->where('period_id', $periodId)
        ->where('check_date', $date)
        ->select('check_time', 'check_type', 'period_id')
        ->orderBy('id', 'asc')
    // ->groupBy('period_id')
        ->get();
    return $attenance;
}
/**
 * get employees attendances
 */

function employeeAttendancesByDate(array $employeeIds, $date)
{
    // Convert the date to a Carbon instance for easier manipulation
    $date = Carbon::parse($date);

    // Get weekend days from the WeeklyHoliday model
    $weekend_days = json_decode(WeeklyHoliday::select('days')?->first()?->days);

    // Fetch holidays on the specific date
    $holiday = Holiday::where('active', 1)
        ->whereDate('from_date', '<=', $date)
        ->whereDate('to_date', '>=', $date)
        ->select('from_date', 'to_date', 'count_days', 'name')
        ->first();

    // Initialize an array to hold the results for multiple employees
    $result = [];

    // Loop through each employee ID
    foreach ($employeeIds as $employeeId) {
        // Fetch the employee by ID

        $employee = Employee::where('id', $employeeId)->whereHas('periods')->first();
        if ($employee) {

            // if (!$employee) {
            //     // Skip if the employee doesn't exist or doesn't have periods
            //     $result[$employeeId] = ['error' => 'Employee not found or has no periods'];
            //     continue;
            // }

            // $employee = Employee::find($employeeId)->wherehas('periods');
            // if (!$employee) {
            //     // Skip if the employee doesn't exist
            //     $result[$employeeId] = ['error' => 'Employee not found'];
            //     continue;
            // }

            // Initialize the result for the current employee and date
            $result[$employeeId][$date->toDateString()] = [
                'employee_id' => $employeeId,
                'employee_name' => $employee->name,
                'date' => $date->toDateString(),
                'day' => $date->format('l'),
                'periods' => [],
            ];

            // Skip if the date is a weekend
            if ($weekend_days != null && in_array($date->format('l'), $weekend_days)) {
                $result[$employeeId][$date->toDateString()]['status'] = 'weekend';
                continue; // Skip weekends
            }

            // Check if the current date is a holiday
            if ($holiday) {
                $result[$employeeId][$date->toDateString()]['holiday'] = [
                    'name' => $holiday->name,
                ];
                continue; // Skip to the next employee if it's a holiday
            }

            // Fetch leave applications for the specific date
            $leave = $employee->approvedLeaveApplications()
                ->whereDate('from_date', '<=', $date)
                ->whereDate('to_date', '>=', $date)
                ->select('from_date', 'to_date', 'leave_type_id')
                ->with('leaveType:id,name') // Assuming you have a relationship defined
                ->first();

            // Check if the current date falls within any leave applications
            if ($leave) {
                $result[$employeeId][$date->toDateString()]['leave'] = [
                    'leave_type_id' => $leave->leave_type_id,
                    'leave_type_name' => $leave->leaveType->name ?? 'Unknown', // Include leave type name
                ];
                continue; // Skip to the next employee if it's a leave day
            }

            // Get the employee's work periods for the current date
            $employeePeriods = DB::table('hr_work_periods as wp')
                ->join('hr_employee_periods as ep', 'wp.id', '=', 'ep.period_id')
                ->select('wp.start_at', 'wp.end_at', 'ep.period_id')
                ->where('ep.employee_id', $employeeId)
                ->orderBy('wp.start_at', 'asc')
                ->get()

            ;

            if ($employeePeriods->isEmpty()) {
                // If no periods are assigned to the employee, mark with a message
                $result[$employeeId][$date->toDateString()]['status'] = 'no periods assigned for this employee';
                $result[$employeeId][$date->toDateString()]['no_periods'] = true;
                continue; // Skip further processing for this employee
            }

            // Loop through each period for the employee
            foreach ($employeePeriods as $period) {
                // Get attendances for the current period and date
                $attendances = DB::table('hr_attendances_backup2 as a')
                    ->where('a.employee_id', '=', $employeeId)
                    ->whereDate('a.check_date', '=', $date)
                    ->where('a.period_id', '=', $period->period_id)
                    ->select('a.*') // Adjust selection as needed
                    ->whereNull('a.deleted_at')
                    ->get();

                // Structure for the current period
                $periodData = [
                    'start_at' => $period->start_at,
                    'end_at' => $period->end_at,
                    'period_id' => $period->period_id,
                    'attendances' => [],
                ];

                // Group attendances by check_type
                if ($attendances->isNotEmpty()) {
                    foreach ($attendances as $attendance) {
                        if ($attendance->check_type === 'checkin') {
                            $periodData['attendances']['checkin'][] = [
                                'check_time' => $attendance->check_time ?? null, // Include check_time
                                'early_arrival_minutes' => $attendance->early_arrival_minutes ?? 0,
                                'delay_minutes' => $attendance->delay_minutes ?? 0,
                                'status' => $attendance->status ?? 'unknown',
                            ];
                        } elseif ($attendance->check_type === 'checkout') {

                            $lastCheckout = [
                                'check_time' => $attendance->check_time ?? null, // Include check_time
                                'status' => $attendance->status ?? 'unknown',
                                'actual_duration_hourly' => $attendance->actual_duration_hourly ?? 0,
                                'total_actual_duration_hourly' => $attendance->total_actual_duration_hourly ?? 0,
                                'supposed_duration_hourly' => $attendance->supposed_duration_hourly ?? 0,
                                'early_departure_minutes' => $attendance->early_departure_minutes ?? 0,
                                'late_departure_minutes' => $attendance->late_departure_minutes ?? 0,
                            ];
                            $periodData['attendances']['checkout'][] = [
                                'check_time' => $attendance->check_time ?? null, // Include check_time
                                'status' => $attendance->status ?? 'unknown',
                                'actual_duration_hourly' => $attendance->actual_duration_hourly ?? 0,
                                'total_actual_duration_hourly' => $attendance->total_actual_duration_hourly ?? 0,
                                'supposed_duration_hourly' => $attendance->supposed_duration_hourly ?? 0,
                                'early_departure_minutes' => $attendance->early_departure_minutes ?? 0,
                                'late_departure_minutes' => $attendance->late_departure_minutes ?? 0,
                            ];
                            $periodData['attendances']['checkout']['lastcheckout'] = $lastCheckout;
                        }
                    }
                } else {
                    // If there are no attendances, mark the period as "absent"
                    $periodData['attendances'] = 'absent';
                }

                // Add the period data to the result for the current employee and date
                $result[$employeeId][$date->toDateString()]['periods'][] = $periodData;
            }
        }
    }

    return $result;
}

function calculateTotalAbsentDays($attendanceData)
{
    $totalAbsentDays = 0;

    foreach ($attendanceData as $date => $data) {
        // Check if periods exist for the date
        if (isset($data['periods']) && !empty($data['periods'])) {
            $allAbsent = true; // Assume all are absent initially

            // Loop through each period to check attendance
            foreach ($data['periods'] as $period) {
                if (isset($period['attendances']) && $period['attendances'] !== 'absent') {
                    $allAbsent = false; // Found a period that is not absent
                    break; // No need to check further
                }
            }

            // If all periods were absent, increment the count
            if ($allAbsent) {
                $totalAbsentDays++;
            }
        }
    }

    return $totalAbsentDays;
}

function calculateTotalLateArrival($attendanceData)
{
    $totalDelayMinutes = 0;

    // Loop through each date in the attendance data
    foreach ($attendanceData as $date => $data) {
        if (isset($data['periods'])) {
            // Loop through each period for the date
            foreach ($data['periods'] as $period) {
                if (isset($period['attendances']['checkin'])) {
                    // Loop through each checkin record
                    foreach ($period['attendances']['checkin'] as $checkin) {
                        // Check if the status is 'late_arrival'
                        if (isset($checkin['status']) && $checkin['status'] === Attendance::STATUS_LATE_ARRIVAL) {
                            // Add the delay minutes to the total
                            $totalDelayMinutes += $checkin['delay_minutes'];
                        }
                    }
                }
            }
        }
    }
    // Calculate total hours as a float
    $totalHoursFloat = $totalDelayMinutes / 60;

    return [
        'totalMinutes' => $totalDelayMinutes,
        'totalHoursFloat' => round($totalHoursFloat, 1),
    ];
}

function calculateAbsentDaysAndDeductSalary($empId, $date)
{
    return calculateMonthlySalaryV2($empId, $date);
}

/**
 * to get months
 */
function getMonthsArray()
{
    return [
        'January' => [
            'name' => __('lang.month.january'), // English Translation
            'start_month' => '2024-01-01',
            'end_month' => '2024-01-31',
        ],
        'February' => [
            'name' => __('lang.month.february'), // English Translation
            'start_month' => '2024-02-01',
            'end_month' => '2024-02-29', // Adjust for leap years as needed
        ],
        'March' => [
            'name' => __('lang.month.march'), // English Translation
            'start_month' => '2024-03-01',
            'end_month' => '2024-03-31',
        ],
        'April' => [
            'name' => __('lang.month.april'), // English Translation
            'start_month' => '2024-04-01',
            'end_month' => '2024-04-30',
        ],
        'May' => [
            'name' => __('lang.month.may'), // English Translation
            'start_month' => '2024-05-01',
            'end_month' => '2024-05-31',
        ],
        'June' => [
            'name' => __('lang.month.june'), // English Translation
            'start_month' => '2024-06-01',
            'end_month' => '2024-06-30',
        ],
        'July' => [
            'name' => __('lang.month.july'), // English Translation
            'start_month' => '2024-07-01',
            'end_month' => '2024-07-31',
        ],
        'August' => [
            'name' => __('lang.month.august'), // English Translation
            'start_month' => '2024-08-01',
            'end_month' => '2024-08-31',
        ],
        'September' => [
            'name' => __('lang.month.september'), // English Translation
            'start_month' => '2024-09-01',
            'end_month' => '2024-09-30',
        ],
        'October' => [
            'name' => __('lang.month.october'), // English Translation
            'start_month' => '2024-10-01',
            'end_month' => '2024-10-31',
        ],
        'November' => [
            'name' => __('lang.month.november'), // English Translation
            'start_month' => '2024-11-01',
            'end_month' => '2024-11-30',
        ],
        'December' => [
            'name' => __('lang.month.december'), // English Translation
            'start_month' => '2024-12-01',
            'end_month' => '2024-12-31',
        ],
    ];
}

/**
 * to get data of salary slip
 */

function employeeSalarySlip($employeeId, $yearMonth)
{
    $salaryDetail = MonthSalaryDetail::where('employee_id', $employeeId)
        ->whereHas('monthSalary', function ($query) use ($yearMonth) {
            $query->where('month', $yearMonth);
        })
        ->get()->first();
    return $salaryDetail;

}
