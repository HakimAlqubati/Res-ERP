<?php

use App\Models\Attendance;
use App\Models\Employee;
use App\Models\Holiday;
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
    if (count(auth()->user()?->roles) > 0) {
        $roleId = auth()->user()?->roles[0]?->id;
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
function calculateMonthlySalary($employeeId, $date)
{
    // Retrieve the employee model with relations to deductions, allowances, and incentives
    $employee = Employee::with(['deductions', 'allowances', 'monthlyIncentives'])->find($employeeId);

    if (!$employee) {
        return 'Employee not found!';
    }

    // Basic salary from the employee model
    $basicSalary = $employee->salary;

    // Calculate total deductions
    $totalDeductions = $employee->deductions->sum(function ($deduction) {
        return $deduction->amount;
    });

    // Calculate total allowances
    $totalAllowances = $employee->allowances->sum(function ($allowance) {
        return $allowance->amount;
    });

    // Calculate total monthly incentives
    $totalMonthlyIncentives = $employee->monthlyIncentives->sum(function ($incentive) {
        return $incentive->amount;
    });

    // Calculate daily and hourly salary
    $dailySalary = calculateDailySalary($employeeId, $date);
    $hourlySalary = calculateHourlySalary($employeeId, $date);

    $overtimeHours = getEmployeeOvertimes($date, $employee);
    // Calculate overtime pay (overtime hours paid at double the regular hourly rate)
    $overtimePay = $overtimeHours * $hourlySalary * 2;

    // Calculate net salary including overtime
    $netSalary = $basicSalary + $totalAllowances + $totalMonthlyIncentives + $overtimePay - $totalDeductions;

    // Return the details and net salary breakdown
    return [
        'net_salary' => $netSalary,
        'details' => [
            'basic_salary' => $basicSalary,
            'total_deductions' => $totalDeductions,
            'total_allowances' => $totalAllowances,
            'total_monthly_incentives' => $totalMonthlyIncentives,
            'overtime_hours' => $overtimeHours,
            'overtime_pay' => $overtimePay,
            'another_details' => [
                'daily_salary' => $dailySalary,
                'hourly_salary' => $hourlySalary,
                'days_in_month' => getDaysInMonth($date),
            ],
        ],
    ];
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

// Sum the 'hours' field for the filtered overtimes
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
// {
//     // Decode the days array for each period
//     $workTimePeriods = $employee->periods->map(function ($period) {
//         $period->days = json_decode($period->days); // Ensure days are decoded
//         return $period;
//     });

//     // Filter periods by the day
//     $periodsForDay = $workTimePeriods->filter(function ($period) use ($day) {
//         return in_array($day, $period->days);
//     });

//     // Check if no periods are found for the given day
//     if ($periodsForDay->isEmpty()) {
//         return "There is no period for today.";
//     }

//     // Convert the input time to a Carbon instance
//     $checkTime = \Carbon\Carbon::createFromFormat('H:i:s', $time);

//     // Sort periods based on proximity to check-in or check-out
//     $nearestPeriod = $periodsForDay->sortBy(function ($period) use ($checkTime, $checkType) {
//         $startTime = \Carbon\Carbon::createFromFormat('H:i:s', $period->start_at);
//         $endTime = \Carbon\Carbon::createFromFormat('H:i:s', $period->end_at);

//         if ($checkType == Attendance::CHECKTYPE_CHECKIN) {
//             // For check-in, find the nearest start time
//             return abs($checkTime->diffInMinutes($startTime, false));
//         } elseif ($checkType == Attendance::CHECKTYPE_CHECKOUT) {
//             // For check-out, find the nearest end time
//             return abs($checkTime->diffInMinutes($endTime, false));
//         }

//         return PHP_INT_MAX;
//     })->first();

//     // If no period is found based on proximity
//     if (!$nearestPeriod) {
//         return "There is no period that matches your check-in/check-out time.";
//     }

//     // Prepare data array
//     $data = [];
//     $data['period_id'] = $nearestPeriod->id;
//     $allowedLateMinutes = $nearestPeriod?->allowed_count_minutes_late;
//     $startTime = \Carbon\Carbon::parse($nearestPeriod->start_at);
//     $endTime = \Carbon\Carbon::parse($nearestPeriod->end_at);

//     // Handle check-in scenario
//     if ($checkType == Attendance::CHECKTYPE_CHECKIN) {
//         if ($checkTime->gt($startTime)) {
//             // Employee is late
//             $data['delay_minutes'] = $startTime->diffInMinutes($checkTime);
//             $data['early_arrival_minutes'] = 0;
//             if ($allowedLateMinutes > 0) {
//                 $data['status'] = ($data['delay_minutes'] <= $allowedLateMinutes) ? Attendance::STATUS_ON_TIME : Attendance::STATUS_LATE_ARRIVAL;
//             } else {
//                 $data['status'] = Attendance::STATUS_LATE_ARRIVAL;
//             }
//         } else {
//             // Employee is early
//             $data['delay_minutes'] = 0;
//             $data['early_arrival_minutes'] = $checkTime->diffInMinutes($startTime);
//             $data['status'] = ($data['early_arrival_minutes'] == 0) ? Attendance::STATUS_ON_TIME : Attendance::STATUS_EARLY_ARRIVAL;
//         }
//         $data['late_departure_minutes'] = 0;
//     }

//     // Handle check-out scenario
//     elseif ($checkType == Attendance::CHECKTYPE_CHECKOUT) {
//         // Find the corresponding check-in record
//         $checkinRecord = Attendance::where('employee_id', $employee->id)
//             ->where('period_id', $data['period_id'])
//             ->where('check_type', Attendance::CHECKTYPE_CHECKIN)
//             ->whereDate('check_date', $checkDate)
//             ->first();

//         if ($checkinRecord) {
//             $checkinTime = \Carbon\Carbon::parse($checkinRecord->check_time);

//             // Calculate the actual duration (from check-in to check-out)
//             $actualDuration = $checkinTime->diff($checkTime);
//             $hoursActual = $actualDuration->h;
//             $minutesActual = $actualDuration->i;

//             // Calculate the supposed duration (from period start to end)
//             $supposedDuration = $startTime->diff($endTime);
//             $hoursSupposed = $supposedDuration->h;
//             $minutesSupposed = $supposedDuration->i;

//             // Store both durations in a format like "hours:minutes"
//             $data['actual_duration_hourly'] = sprintf('%02d:%02d', $hoursActual, $minutesActual);
//             $data['supposed_duration_hourly'] = sprintf('%02d:%02d', $hoursSupposed, $minutesSupposed);
//         }

//         // Calculate late departure or early departure
//         if ($checkTime->gt($endTime)) {
//             // Late departure
//             $data['late_departure_minutes'] = $endTime->diffInMinutes($checkTime);
//             $data['early_departure_minutes'] = 0;
//             $data['status'] = Attendance::STATUS_LATE_DEPARTURE;
//         } else {
//             // Early departure
//             $data['late_departure_minutes'] = 0;
//             $data['early_departure_minutes'] = $checkTime->diffInMinutes($endTime);
//             $data['status'] = Attendance::STATUS_EARLY_DEPARTURE;
//         }
//         $data['delay_minutes'] = 0;
//     }

//     // Handle case where no matching period is found
//     else {
//         $data['period_id'] = 0;
//         $data['delay_minutes'] = 0;
//         $data['early_arrival_minutes'] = 0;
//         $data['late_departure_minutes'] = 0;
//         $data['early_departure_minutes'] = 0;
//     }

//     // Return the processed data
//     return $data;
// }
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
    $weekend_days = json_decode(WeeklyHoliday::select('days')->first()->days);

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
        if (in_array($date->format('l'), $weekend_days)) {
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
        if (is_array($leaveApplications)) {
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
            ->select('wp.start_at', 'wp.end_at', 'ep.period_id')
            ->where('ep.employee_id', $employeeId)
            ->get();

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
                        $periodData['attendances']['checkout'][] = [
                            'check_time' => $attendance->check_time ?? null, // Include check_time
                            'status' => $attendance->status ?? 'unknown',
                            'actual_duration_hourly' => $attendance->actual_duration_hourly ?? 0,
                            'supposed_duration_hourly' => $attendance->supposed_duration_hourly ?? 0,
                            'early_departure_minutes' => $attendance->early_departure_minutes ?? 0,
                            'late_departure_minutes' => $attendance->late_departure_minutes ?? 0,
                        ];
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

    return $result;
}

/**
 * get employees attendances
 */

 function employeeAttendancesByDate(array $employeeIds, $date)
 {
     // Convert the date to a Carbon instance for easier manipulation
     $date = Carbon::parse($date);
 
     // Get weekend days from the WeeklyHoliday model
     $weekend_days = json_decode(WeeklyHoliday::select('days')->first()->days);
 
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
         $employee = Employee::find($employeeId);
         if (!$employee) {
             // Skip if the employee doesn't exist
             $result[$employeeId] = ['error' => 'Employee not found'];
             continue;
         }
 
         // Initialize the result for the current employee and date
         $result[$employeeId][$date->toDateString()] = [
             'employee_id' => $employeeId,
             'employee_name' => $employee->name,
             'date' => $date->toDateString(),
             'day' => $date->format('l'),
             'periods' => [],
         ];
 
         // Skip if the date is a weekend
         if (in_array($date->format('l'), $weekend_days)) {
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
             ->get();
 
         if ($employeePeriods->isEmpty()) {
             // If no periods are assigned to the employee, mark with a message
             $result[$employeeId][$date->toDateString()]['status'] = 'no periods assigned for this employee';
             $result[$employeeId][$date->toDateString()]['no_periods'] = true;
             continue; // Skip further processing for this employee
         }
 
         // Loop through each period for the employee
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
                         $periodData['attendances']['checkout'][] = [
                             'check_time' => $attendance->check_time ?? null, // Include check_time
                             'status' => $attendance->status ?? 'unknown',
                             'actual_duration_hourly' => $attendance->actual_duration_hourly ?? 0,
                             'supposed_duration_hourly' => $attendance->supposed_duration_hourly ?? 0,
                             'early_departure_minutes' => $attendance->early_departure_minutes ?? 0,
                             'late_departure_minutes' => $attendance->late_departure_minutes ?? 0,
                         ];
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
 
     return $result;
 }
 
 
