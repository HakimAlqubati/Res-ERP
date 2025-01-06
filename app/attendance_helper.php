<?php

use App\Models\Attendance;
use App\Models\Employee;
use App\Models\EmployeePeriodHistory;
use App\Models\Holiday;
use App\Models\Setting;
use App\Models\WeeklyHoliday;
use App\Models\WorkPeriod;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

function formatDuration($duration)
{
    // Check if the duration is null or an empty string
    if (is_null($duration) || $duration === '') {
        return '0 h 0 m'; // Default value for null duration
    }

    // Split the duration string by colon
    list($hours, $minutes, $seconds) = explode(':', $duration);

    // Return the formatted string
    return "{$hours} h " . (int) $minutes . " m"; // Cast minutes to int to avoid any zero-padding
}


/**
 * to get employee periods based on history
 */
function getEmployeePeriods($employeeId, $startDate, $endDate): Collection
{
    // Fetch periods from hr_employee_period_histories based on employee_id and date range
    $periods = EmployeePeriodHistory::select(
        DB::raw("TIME(start_date) as start_at"), // Extract time from start_date
        DB::raw("TIME(end_date) as end_at"), // Extract time from end_date
        'period_id'
    )
        ->where('employee_id', $employeeId)
        ->whereBetween('start_date', [$startDate, $endDate])
        ->get();

    // Format the results to match the specified structure
    return $periods->map(function ($period) {
        return (object) [
            'start_at' => $period->start_at,
            'end_at' => $period->end_at,
            'period_id' => $period->period_id,
        ];
    });
}

function getPeriodsForDateRange($employeeId, Carbon $startDate, Carbon $endDate): Collection
{
    // Initialize a collection to hold the results, grouped by date
    $groupedPeriods = collect();

    // Loop through each date in the date range
    for ($date = $startDate->copy(); $date->lessThanOrEqualTo($endDate); $date->addDay()) {
        // Fetch periods that include the current date
        $periods = EmployeePeriodHistory::select('period_id', 'start_date', 'end_date', 'start_time', 'end_time')
            ->where('employee_id', $employeeId)
            ->where('active', 1)
            ->where(function ($query) use ($date) {
                $query->where('start_date', '<=', $date) // Period starts before or on the date
                    ->where(function ($subQuery) use ($date) {
                        $subQuery->where('end_date', '>=', $date) // Period ends after or on the date
                            ->orWhereNull('end_date'); // Active periods without an end date
                    });
            })
            ->get();

        // If there are periods for the current date, group them under that date
        if ($periods->isNotEmpty()) {
            $groupedPeriods->put($date->toDateString(), $periods->map(function ($period) {
                return [
                    'period_id' => $period->period_id,
                    'start_date' => $period->start_date,
                    'end_date' => $period->end_date,
                    'start_at' => $period->start_time,
                    'end_at' => $period->end_time,
                ];
            }));
        }
    }
    return $groupedPeriods;
}

/**
 * to return the employees that absent in specific day, or maybe forget checkin
 */
function reportAbsentEmployees($date, $branchId, $currentTime)
{
    $employees = Employee::where('branch_id', $branchId)
        ->with(['periods' => function ($query) {
            $query->select('hr_work_periods.id', 'hr_work_periods.name', 'hr_work_periods.start_at', 'hr_work_periods.end_at')
                ->whereNull('hr_work_periods.deleted_at');
        }])
        ->select('id', 'name', 'employee_no', 'branch_id')
        ->get();

    $absentEmployees = [];

    // Loop through employees and check if they have attendance for the date
    foreach ($employees as $employee) {
        $attendance = $employee->attendancesByDate($date)->exists();
        $isPeriodEnded = false;

        // Check if any of the periods' end time is less than the current time
        foreach ($employee->periods as $period) {
            if ($currentTime > $period->start_at) {
                $isPeriodEnded = true;
                break;
            }
        }

        // If the employee is absent and period has ended, add to absentEmployees
        if (!$attendance && $isPeriodEnded) {
            $absentEmployees[] = [
                'name' => $employee->name,
                'employee_no' => $employee->employee_no,
                'periods' => $employee->periods->map(function ($period) {
                    return [
                        'id' => $period->id,
                        'name' => $period->name,
                        'start_at' => $period->start_at,
                        'end_at' => $period->end_at,
                    ];
                })
            ];
        }
    }

    return $absentEmployees;
}

if (!function_exists('ordinal')) {
    function ordinal($number)
    {
        $suffixes = ['th', 'st', 'nd', 'rd', 'th', 'th', 'th', 'th', 'th', 'th'];
        return $number . ($suffixes[($number % 100 >= 11 && $number % 100 <= 13) ? 0 : $number % 10]);
    }
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


    // dd($leaveApplications);
    $leaveApplications =  $employee?->approvedLeaveApplications()
        ->join('hr_leave_requests', 'hr_employee_applications.id', '=', 'hr_leave_requests.application_id')
        ->where(function ($query) use ($startDate, $endDate) {
            $query->whereBetween('hr_leave_requests.start_date', [$startDate, $endDate])
                ->orWhereBetween('hr_leave_requests.end_date', [$startDate, $endDate]);
        })
        ->join('hr_leave_types', 'hr_leave_requests.leave_type', '=', 'hr_leave_types.id')
        ->select(
            'hr_leave_requests.start_date as from_date',
            'hr_leave_requests.end_date as to_date',
            'hr_leave_requests.leave_type',
            'hr_leave_types.name as transaction_description',
        )->get();
    // dd($leaveApplications);
    // Initialize an array to hold the results
    $result = [];
    $employeeHistoryPeriods = getPeriodsForDateRange($employeeId, $startDate, $endDate);
    // foreach ($variable as $key => $value) {
    //     # code...
    // }

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
            // dd($leaveApplications);
            foreach ($leaveApplications as $leave) {
                // dd($leave);
                if ($date->isBetween($leave->from_date, $leave->to_date, true)) {
                    $result[$date->toDateString()]['leave'] = [
                        'leave_type_id' => $leave->leave_type,
                        'transaction_description' => $leave->transaction_description ?? 'Unknown', // Include leave type name
                    ];
                    continue 2; // Skip to the next date if it's a leave day
                }
            }
        }


        if ($leaveApplications) {
            foreach ($leaveApplications as $leaveApplication) {
                // dd($leaveApplication->transaction_description,$leaveApplication);
                if ($date->isBetween($leaveApplication->from_date, $leaveApplication->to_date, true)) {
                    $result[$date->toDateString()]['leave'] = [
                        'leave_type_id' => $leaveApplication->leave_type_id,
                        'transaction_description' => $leaveApplication->transaction_description, // Include leave type name
                    ];
                    continue 2; // Skip to the next date if it's a leave day
                }
            }
        }


        $employeePeriods = $employeeHistoryPeriods[$date->toDateString()] ?? [];

        // dd($employeePeriods,$employeePeriods2);

        if (count($employeePeriods) == 0) {
            $result[$date->toDateString()]['no_periods'] = true;
        }
        foreach ($employeePeriods as $period) {
            $period = (object) $period;

            // Get attendances for the current period and date
            $attendances = DB::table('hr_attendances as a')
                ->where('a.employee_id', '=', $employeeId)
                ->whereDate('a.check_date', '=', $date)
                ->where('a.period_id', '=', $period->period_id)
                ->select('a.*') // Adjust selection as needed
                ->whereNull('a.deleted_at')
                ->get();

            // Structure for the current period
            $periodData = [
                'date' => $date->toDateString(),
                'start_date' => $period->start_date,
                'end_date' => $period->end_date,
                'start_at' => $period->start_at,
                'end_at' => $period->end_at,
                'period_id' => $period->period_id,
                'total_hours' => $employee->calculateTotalWorkHours($period->period_id, $date),
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
                                'check_date' => $attendance->check_date,
                                'early_arrival_minutes' => $attendance->early_arrival_minutes ?? 0,
                                'delay_minutes' => $attendance->delay_minutes ?? 0,
                                'status' => $attendance->status ?? 'unknown',
                            ];
                        }
                        $periodData['attendances']['checkin']['firstcheckout'] = $lastCheckout;
                        $periodData['attendances']['checkin'][] = [
                            'check_time' => $attendance->check_time ?? null, // Include check_time
                            'early_arrival_minutes' => $attendance->early_arrival_minutes ?? 0,
                            'delay_minutes' => $attendance->delay_minutes ?? 0,
                            'status' => $attendance->status ?? 'unknown',
                        ];
                    } elseif ($attendance->check_type === 'checkout') {

                        $periodObject = WorkPeriod::find($period->period_id)->supposed_duration;




                        $formattedSupposedActualDuration = formatDuration($attendance->supposed_duration_hourly);
                        $isActualLargerThanSupposed = isActualDurationLargerThanSupposed($periodObject, $periodData['total_hours']);


                        $approvedOvertime = getEmployeeOvertimesOfSpecificDate($date, $employee);
                        if ($isActualLargerThanSupposed && $employee->overtimesByDate($date)->count() > 0) {
                            // if ($isActualLargerThanSupposed &&  $employee->overtimes->count() > 0) {
                            $approvedOvertime = addHoursToDuration($formattedSupposedActualDuration, $approvedOvertime);
                        }
                        if ($isActualLargerThanSupposed && $employee->overtimesByDate($date)->count() == 0) {
                            $approvedOvertime = $formattedSupposedActualDuration;
                        }
                        if (!$isActualLargerThanSupposed) {
                            $approvedOvertime = $periodData['total_hours'];
                        }

                        // dd($approvedOvertime);
                        // dd($approvedOvertime);
                        $lastCheckout = [
                            'check_time' => $attendance->check_time ?? null, // Include check_time
                            'period_end_at' => $period->end_at,
                            'status' => $attendance->status ?? 'unknown',
                            'actual_duration_hourly' => $formattedSupposedActualDuration,
                            'supposed_duration_hourly' => $attendance->supposed_duration_hourly ?? $periodObject . ':00',
                            'early_departure_minutes' => $attendance->early_departure_minutes ?? 0,
                            'late_departure_minutes' => $attendance->late_departure_minutes ?? 0,
                            'total_actual_duration_hourly' => $attendance->total_actual_duration_hourly,
                            'approved_overtime' =>  $approvedOvertime,
                            'missing_hours' =>   calculate_missing_hours(
                                $attendance->status,
                                $attendance->supposed_duration_hourly ?? $periodObject . ':00',
                                $approvedOvertime,
                                $date->toDateString(),
                                $employeeId

                            ),
                        ];

                        $periodData['attendances']['checkout'][] = [
                            'check_time' => $attendance->check_time ?? null, // Include check_time

                            'status' => $attendance->status ?? 'unknown',
                            'actual_duration_hourly' => $formattedSupposedActualDuration,
                            'supposed_duration_hourly' => $attendance->supposed_duration_hourly ?? $periodObject . ':00',
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

            if (count($employeePeriods) == 0) {
                return 'no_periods';
            }
        }
    }
    // dd($result   );
    return $result;
}

function formatHoursMinuts($totalHours)
{
    // Separate hours and minutes
    $hours = floor($totalHours); // Get the integer part (hours)
    $minutes = ($totalHours - $hours) * 60; // Convert fractional part to minutes

    // Format the output
    $formattedTime = "{$hours} h " . round($minutes) . " m";
    return $formattedTime;
}

/**
 * to get employee attendace details of period []
 */
function getEmployeePeriodAttendnaceDetails($employeeId, $periodId, $date)
{
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
 * Compare formatted duration strings to check if the actual duration is larger than the supposed duration.
 *
 * @param string $supposedDuration The supposed duration in "HH:MM" format.
 * @param string $actualDuration The actual duration in "H h M m" format.
 * @return bool Returns true if the actual duration is larger than the supposed duration, false otherwise.
 */
function isActualDurationLargerThanSupposed($supposedDuration, $actualDuration)
{

    // Convert $supposedDuration ("HH:MM") to total minutes
    list($supposedHours, $supposedMinutes) = explode(':', $supposedDuration);
    $supposedTotalMinutes = ($supposedHours * 60) + $supposedMinutes;
    // Convert $actualDuration ("H h M m") to total minutes
    preg_match('/(\d+) h (\d+) m/', $actualDuration, $matches);
    $actualHours = isset($matches[1]) ? (int) $matches[1] : 0;
    $actualMinutes = isset($matches[2]) ? (int) $matches[2] : 0;
    $actualTotalMinutes = ($actualHours * 60) + $actualMinutes;

    // Compare the total minutes
    return $actualTotalMinutes > $supposedTotalMinutes;
}

/**
 * Adds hours to a given time duration string in the format "X h Y m".
 *
 * @param string $duration The initial time duration (e.g., "12 h 0 m").
 * @param int $additionalHours The number of hours to add.
 * @return string The updated time duration string.
 */
function addHoursToDuration($duration, $additionalHours)
{
    // Extract hours and minutes from the duration string
    preg_match('/(\d+)\s*h\s*(\d+)\s*m/', $duration, $matches);

    if ($matches) {
        $hours = (int) $matches[1];
        $minutes = (int) $matches[2];

        // Add the additional hours
        $totalHours = $hours + $additionalHours;
        return formatHoursMinuts($totalHours);
    }
    // Return the original duration if the format is incorrect
    return $duration;
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

        $employee = Employee::where('id', $employeeId)->first();
        // dd($employee);
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

            // // Fetch leave applications for the specific date
            // $leave = $employee->approvedLeaveApplications()
            //     ->whereDate('from_date', '<=', $date)
            //     ->whereDate('to_date', '>=', $date)
            //     ->select('from_date', 'to_date', 'leave_type_id')
            //     ->with('leaveType:id,name') // Assuming you have a relationship defined
            //     ->first();

            // $leaveTransactions = $employee->transactions()
            //     ->where('transaction_type_id', 1) // 1 represents "Leave request"
            //     ->where('is_canceled', false) // Ensure the transaction is not canceled
            //     ->whereDate('from_date', '<=', $date)
            //     ->whereDate('to_date', '>=', $date)
            //     ->get(['from_date', 'to_date', 'amount', 'value', 'transaction_type_id', 'transaction_description'])->first();

            // // Check if the current date falls within any leave applications
            // if ($leave) {
            //     $result[$employeeId][$date->toDateString()]['leave'] = [
            //         'leave_type_id' => $leave->leave_type_id,
            //         'leave_type_name' => $leave->leaveType->name ?? 'Unknown', // Include leave type name
            //     ];
            //     continue; // Skip to the next employee if it's a leave day
            // }

            // if ($leaveTransactions) {

            //     $result[$employeeId][$date->toDateString()]['leave'] = [
            //         'transaction_type_id' => $leaveTransactions->leave_type,
            //         'transaction_description' => $leaveTransactions->transaction_description,
            //     ];
            //     // continue; // Skip to the next employee if it's a leave day
            // }

            $employeePeriods = getPeriodsForDateRange($employeeId, $date, $date)[$date->toDateString()] ?? [];
            // dd($employeePeriods, $employeePeriods2);

            // if ( $employeePeriods->isEmpty()) {
            if (is_array($employeePeriods) && count($employeePeriods) == 0) {
                // If no periods are assigned to the employee, mark with a message
                $result[$employeeId][$date->toDateString()]['status'] = 'no periods assigned for this employee';
                $result[$employeeId][$date->toDateString()]['no_periods'] = true;
                continue; // Skip further processing for this employee
            }

            // Loop through each period for the employee
            foreach ($employeePeriods as $period) {
                $period = (object) $period;

                // Get attendances for the current period and date
                $attendances = DB::table('hr_attendances as a')
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
                    'total_hours' => $employee->calculateTotalWorkHours($period->period_id, $date),
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

                            $periodObject = WorkPeriod::find($period->period_id)->supposed_duration;
                            $formattedSupposedActualDuration = formatDuration($attendance?->supposed_duration_hourly);

                            $isActualLargerThanSupposed = isActualDurationLargerThanSupposed($periodObject, $periodData['total_hours']);

                            $approvedOvertime = getEmployeeOvertimesOfSpecificDate($date, $employee);

                            if ($isActualLargerThanSupposed && $employee->overtimes->count() > 0) {
                                $approvedOvertime = addHoursToDuration($formattedSupposedActualDuration, $approvedOvertime);
                            }
                            if ($isActualLargerThanSupposed && $employee->overtimes->count() == 0) {
                                $approvedOvertime = $formattedSupposedActualDuration;
                            }
                            if (!$isActualLargerThanSupposed) {
                                $approvedOvertime = $periodData['total_hours'];
                            }

                            $lastCheckout = [
                                'check_time' => $attendance->check_time ?? null, // Include check_time
                                'status' => $attendance->status ?? 'unknown',
                                'actual_duration_hourly' => $attendance->actual_duration_hourly ?? 0,
                                'total_actual_duration_hourly' => $attendance->total_actual_duration_hourly ?? 0,
                                'supposed_duration_hourly' => $formattedSupposedActualDuration != '0 h 0 m' ? $formattedSupposedActualDuration : $periodObject . ':00',
                                'early_departure_minutes' => $attendance->early_departure_minutes ?? 0,
                                'late_departure_minutes' => $attendance->late_departure_minutes ?? 0,
                                'approved_overtime' => $approvedOvertime,
                                'is' => $isActualLargerThanSupposed,
                                'period_end_at' => $period->end_at,

                            ];
                            $periodData['attendances']['checkout'][] = [
                                'check_time' => $attendance->check_time ?? null, // Include check_time
                                'status' => $attendance->status ?? 'unknown',
                                'actual_duration_hourly' => $attendance->actual_duration_hourly ?? 0,
                                'total_actual_duration_hourly' => $attendance->total_actual_duration_hourly ?? 0,
                                'supposed_duration_hourly' => $formattedSupposedActualDuration != '0 h 0 m' ? $formattedSupposedActualDuration : $periodObject . ':00',
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
    // dd ($attendanceData);
    $totalAbsentDays = 0;

    $result = [];
    foreach ($attendanceData as $date => $data) {
        // Check if periods exist for the date
        if (isset($data['periods']) && !empty($data['periods'])) {
            $allAbsent = true; // Assume all are absent initially

            // Loop through each period to check attendance
            foreach ($data['periods'] as $period) {
                // dd(array_intersect_key(array_flip(['checkin', 'checkout']), $period['attendances']),$period['attendances']);
                if ((is_array($period['attendances']) && count($period['attendances']) == 1)) {

                    $allAbsent = true; // Found a period that is not absent
                    break; // No need to check further

                }
                if (isset($period['attendances']) && ($period['attendances'] !== 'absent')) {
                    $allAbsent = false; // Found a period that is not absent
                    break; // No need to check further
                }
                // Collect absent periods
                if (isset($period['attendances']) && $period['attendances'] === 'absent') {
                    $result[] = $period['date'];  // Save the absent period for later use
                }
            }

            // If all periods were absent, increment the count
            if ($allAbsent) {
                $totalAbsentDays++;
            }
        }
    }

    return [
        'total_absent_days' => $totalAbsentDays,
        'absent_dates' => $result,
    ];
    dd($totalAbsentDays, $result);
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
                    // dd($date, $period['attendances']['checkin'][0]);
                    // Check if the status is 'late_arrival'
                    if (isset($period['attendances']['checkin'][0]['status']) && $period['attendances']['checkin'][0]['status'] === Attendance::STATUS_LATE_ARRIVAL) {
                        // Add the delay minutes to the total
                        if ($period['attendances']['checkin'][0]['delay_minutes'] > Setting::getSetting('early_attendance_minutes')) {
                            $totalDelayMinutes += $period['attendances']['checkin'][0]['delay_minutes'];
                            // $totalDelayMinutes += 2;
                        }
                    }
                }
            }
        }
    }
    // dd($totalDelayMinutes);
    // Calculate total hours as a float
    $totalHoursFloat = $totalDelayMinutes / 60;

    return [
        'totalMinutes' => $totalDelayMinutes,
        'totalHoursFloat' => round($totalHoursFloat, 1),
    ];
}

function calculateTotalEarlyLeave($attendanceData)
{
    // dd($attendanceData);
    $totalEarlyLeaveMinutes = 0;
    // return 23;
    // Loop through each date in the attendance data
    foreach ($attendanceData as $date => $data) {

        if (isset($data['periods'])) {
            // Loop through each period for the date
            foreach ($data['periods'] as $period) {
                // dd( $period['attendances']['checkout']['lastcheckout']['early_departure_minutes']);
                if (
                    isset($period['attendances']['checkout']['lastcheckout']['status'])
                    && $period['attendances']['checkout']['lastcheckout']['status'] === Attendance::STATUS_EARLY_DEPARTURE
                    && $period['attendances']['checkout']['lastcheckout']['early_departure_minutes'] > setting('early_depature_deduction_minutes')
                ) {

                    // dd($period['attendances']['checkout']['lastcheckout']);
                    // Check if the status is 'early_arrival' (early leave)

                    // Add the early leave minutes to the total

                    $totalEarlyLeaveMinutes +=  $period['attendances']['checkout']['lastcheckout']['early_departure_minutes'];
                }
            }
        }
    }

    return round(($totalEarlyLeaveMinutes / 60), 1);
    // Calculate total hours as a float
    $totalHoursFloat = $totalEarlyLeaveMinutes / 60;

    return [
        'totalMinutes' => $totalEarlyLeaveMinutes,
        'totalHoursFloat' => round($totalHoursFloat, 1),
    ];
}

if (!function_exists('calculate_missing_hours')) {
    /**
     * Calculate the difference between supposed duration and approved overtime.
     *
     * @param string|null $supposedDuration The supposed duration in HH:mm:ss format.
     * @param string|null $approvedOvertime The approved overtime in "X h Y m" format.
     * @return string The difference in "X h Y m" format.
     */
    function calculate_missing_hours(
        $status,
        $supposedDuration,
        $approvedOvertime,
        $date,
        $employeeId
    ) {
        $isMultiple = Attendance::where('check_date', $date)->where('employee_id', $employeeId)->where('check_type', Attendance::CHECKTYPE_CHECKIN)->count() > 1 ? true : false;

        if (!$isMultiple) {
            return [
                'formatted' => '0 h 0m',
                'total_minutes' => 0,
            ];
        }
        if (in_array($status, [
            Attendance::STATUS_EARLY_DEPARTURE,
            Attendance::STATUS_LATE_ARRIVAL
        ])) {
            return [
                'formatted' => '0 h 0m',
                'total_minutes' => 0,
            ];
        }
        // Default the supposed duration if null
        $supposedDuration = $supposedDuration ?? '00:00:00';


        $approvedOvertimeParsed = convertToFormattedTime($approvedOvertime);
        // dd($approvedOvertimeParsed);
        if (!Carbon::parse($approvedOvertimeParsed)->lt(Carbon::parse($supposedDuration))) {
            // dd(Carbon::parse($supposedDuration), Carbon::parse($approvedOvertimeParsed));
            return [
                'formatted' => '0 h 0m',
                'total_minutes' => 0,
            ];
        }
        // Calculate the difference
        $difference = Carbon::parse($supposedDuration)->diff(Carbon::parse($approvedOvertimeParsed));

        // Calculate the total number of minutes
        $totalMinutes = $difference->h * 60 + $difference->i;
        $totalHours = round($totalMinutes / 60, 1);
        // Return both formatted difference and total minutes in an array
        return [
            'formatted' => $difference->format('%h h %i m'),
            'total_minutes' => $totalMinutes,
            'total_hours' => $totalHours,
        ];
        // Return the formatted difference
        return $difference->format('%h h %i m');
    }
}

function convertToFormattedTime($timeString)
{
    // Extract hours and minutes
    $timeParts = explode(' h ', $timeString);
    $hours = intval($timeParts[0] ?? 0); // Get hours
    $minutes = intval(str_replace(' m', '', $timeParts[1] ?? 0)); // Get minutes

    // Format as HH:mm
    $sprint = Carbon::parse(sprintf('%02d:%02d', $hours, $minutes));
    return $sprint;
}
