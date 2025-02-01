<?php

use App\Models\Employee;
use App\Models\EmployeeApplicationV2;
use App\Models\LeaveBalance;
use App\Models\LeaveType;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

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


function calculateAutoWeeklyLeaveData($yearAndMonth, $employeeId)
{

    $weeklyLeave = LeaveType::weeklyLeave();
    $yearMonthArr = explode('-', $yearAndMonth);
    $year = $yearMonthArr[0];
    $month = $yearMonthArr[1];
    $date = Carbon::parse($yearAndMonth);
    // Get the start of the month
    $startDate = $date->copy()->startOfMonth()->format('Y-m-d');

    // Get the end of the month
    $endDate = $date->copy()->endOfMonth()->format('Y-m-d');
    $attendances = employeeAttendances($employeeId, $startDate, $endDate);

    $absendCalculated = calculateTotalAbsentDays($attendances);

    $absentDates = $absendCalculated['absent_dates'];
    $totalAttendanceDays = $absendCalculated['total_attendance_days'];

    $leaveBalance = LeaveBalance::getMonthlyBalanceForEmployee($employeeId, $year, $month);
    $usedLeaves = 0;
    // $allowedLeaves = $weeklyLeave->count_days;
    // $allowedLeaves = $leaveBalance->balance ?? 0;
    $allowedLeaves = (int) round($totalAttendanceDays / 7);

    if (isset($leaveBalance->balance) && $leaveBalance->balance > 0) {
        $usedLeaves = $allowedLeaves - $leaveBalance->balance;
    }


    if ($attendances == 'no_periods') {
        return 'no_periods';
    }

    // $balanceDays = round($totalAttendanceDays / 7);
    // dd($absentDates, $totalAttendanceDays, $balanceDays);
    $absentDays = count($absentDates);
    // Final results to return
    $results = [
        'remaining_leaves' => 0,        // Remaining leave days after accounting for used leave and absences
        'compensated_days' => 0,       // Days to be compensated (unused leave days)
        'excess_absence_days' => 0,    // Days of absence exceeding allowed leave
        'absent_days' => $absentDays,
        'absent_dates' => $absentDates,
    ];
    // Case 1: If the employee used fewer leaves than allowed
    if ($absentDays < $allowedLeaves) {
        $remainingLeaves = $allowedLeaves - $usedLeaves;

        // Sub-case 1.1: Employee did not have any absences
        if ($absentDays == 0) {
            $results['remaining_leaves'] = $remainingLeaves;
            $results['compensated_days'] = $remainingLeaves;
        } elseif ($absentDays <= $remainingLeaves) {
            // Sub-case 1.2: Absences are within the remaining leave allowance
            $results['remaining_leaves'] = $remainingLeaves - $absentDays;
            $results['compensated_days'] = $remainingLeaves - $absentDays;
        } else {
            // Sub-case 1.3: Absences exceed the remaining leave allowance
            $results['remaining_leaves'] = 0;
            $results['excess_absence_days'] = $absentDays - $remainingLeaves;
        }
    } else {
        // Case 2: If the employee used all allowed leave
        if ($absentDays > $allowedLeaves) {
            $results['excess_absence_days'] = $absentDays - $allowedLeaves;
            // $results['excess_absence_days'] = $absentDays;
            // dd('d');
            // dd($totalAttendanceDays, round($totalAttendanceDays / 7));
            // $results['excess_absence_days'] = (int) round($totalAttendanceDays / 7);
        }
    }
    // Return the final results as an array
    return $results;
}

function calculateAutoWeeklyLeaveDataForBranch($yearAndMonth, $branchId)
{
    $weeklyLeave = LeaveType::weeklyLeave();
    $yearMonthArr = explode('-', $yearAndMonth);
    $year = $yearMonthArr[0];
    $month = $yearMonthArr[1];

    $employees = Employee::where('branch_id', $branchId)->get();

    $branchResults = [];

    foreach ($employees as $employee) {
        $employeeId = $employee->id;
        $employeeName = $employee->name;
        $leaveBalance = LeaveBalance::getMonthlyBalanceForEmployee($employeeId, $year, $month);
        $usedLeaves = 0;
        $allowedLeaves = $weeklyLeave->count_days;
        $date = Carbon::parse($yearAndMonth);
        $startDate = $date->copy()->startOfMonth()->format('Y-m-d');
        $endDate = $date->copy()->endOfMonth()->format('Y-m-d');

        $attendances = employeeAttendances($employeeId, $startDate, $endDate);
        $absentDates = calculateTotalAbsentDays($attendances)['absent_dates'];
        $absendCalculated = calculateTotalAbsentDays($attendances);

        $absentDates = $absendCalculated['absent_dates']; 
        $totalAttendanceDays = $absendCalculated['total_attendance_days'];
        $absentDays = count($absentDates);

        $allowedLeaves = (int) round($totalAttendanceDays / 7);
        if (isset($leaveBalance->balance) && $leaveBalance->balance > 0) {
            $usedLeaves = $allowedLeaves - $leaveBalance->balance;
        }


        if ($attendances == 'no_periods') {
            $branchResults[$employeeId] = 'no_periods';
            continue;
        }


        if ($absentDays < $allowedLeaves) {
            $results = [
                'employee_id' => $employeeId,
                'employee_name' => $employeeName,
                'remaining_leaves' => 0,
                'compensated_days' => 0,
                'excess_absence_days' => 0,
                'absent_days' => $absentDays,
                'absent_dates' => $absentDates,
            ];

            if ($usedLeaves < $allowedLeaves) {
                $remainingLeaves = $allowedLeaves - $usedLeaves;

                if ($absentDays == 0) {
                    $results['remaining_leaves'] = $remainingLeaves;
                    $results['compensated_days'] = $remainingLeaves;
                } elseif ($absentDays <= $remainingLeaves) {
                    $results['remaining_leaves'] = $remainingLeaves - $absentDays;
                    $results['compensated_days'] = $remainingLeaves - $absentDays;
                } else {
                    $results['remaining_leaves'] = 0;
                    $results['excess_absence_days'] = $absentDays - $remainingLeaves;
                }
            } else {
                if ($absentDays > $allowedLeaves) {
                    $results['excess_absence_days'] = $absentDays - $allowedLeaves;
                }
            }

            $branchResults[$employeeId] = $results;
        }
    }

    return $branchResults;
}
