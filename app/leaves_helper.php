<?php

use App\Models\Employee;
use App\Models\EmployeeApplicationV2;
use App\Models\LeaveBalance;
use App\Models\LeaveType;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

function checkForMonthlyBalanceAndCreateToCancelAbsent($employeeId, $branchId, $year, $month, $allowedLeaves, $leaveTypeId, $absentDates, $leaveBalance)
{
    // DB::beginTransaction();
    // try {
    if (is_numeric($allowedLeaves) && $allowedLeaves > 0 && is_array($absentDates) && count($absentDates) > 0) {

        $i = 1;
        foreach ($absentDates as  $date) {
            if ($i <= $allowedLeaves) {

                EmployeeApplicationV2::create([
                    'employee_id' => $employeeId,
                    'branch_id' => $branchId,
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
                    'employee_id' => $employeeId,
                    'leave_type' => $leaveTypeId,
                    'year' => $year,
                    'month' => $month,
                    'start_date' => $date,
                    'end_date' => $date,
                    'days_count' => 1,
                ]);
            }
            $i++;
        }
        // $leaveBalance->decrement('balance', $allowedLeaves);

        $leaveBalance
            ->update([
                'balance' => $leaveBalance->balance -  $allowedLeaves,
            ]);
        // Log::alert('done_created_auto_monthly_leave', ['employee' => [$employeeId], 'absentDates' => $absentDates]);

        // DB::commit();
        return ['result' => true];
    }
    // } catch (\Throwable $th) {
    //     //throw $th;
    //     DB::rollBack();
    //     Log::error('failed_creating_auto_monthly', ['error' => $th]);
    //     return ['result' => false];
    // }
}


function calculateAutoWeeklyLeaveData($yearAndMonth, $employeeId)
{
    $yearMonthArr = explode('-', $yearAndMonth);
    $year = $yearMonthArr[0];
    $month = $yearMonthArr[1];
    $date = Carbon::parse($yearAndMonth);
    // Get the start of the month
    $startDate = $date->copy()->startOfMonth()->format('Y-m-d');

    // Get the end of the month
    $endDate = $date->copy()->endOfMonth()->format('Y-m-d');
    $attendances = employeeAttendances($employeeId, $startDate, $endDate);
    $employee = Employee::find($employeeId);
    $leaveRequestsCount = $employee->leaveApplications()->whereHas('leaveRequest', function ($query) use ($year, $month) {
        $query->where('year', $year)
            ->where('month', $month);
    })->count();

    $absendCalculated = calculateTotalAbsentDays($attendances);

    $absentDates = $absendCalculated['absent_dates'];
    $totalAttendanceDays = $absendCalculated['total_attendance_days'];

    $leaveBalance = LeaveBalance::getMonthlyBalanceForEmployee($employeeId, $year, $month);
    $usedLeaves = 0;
    // $allowedLeaves = $weeklyLeave->count_days;
    // $allowedLeaves = $leaveBalance->balance ?? 0;
    $allowedLeaves = (int) round($totalAttendanceDays / 7);

    if (isset($leaveBalance->balance) && $leaveBalance->balance > 0 && $leaveRequestsCount == 0) {
        $usedLeaves = $allowedLeaves - $leaveBalance->balance;
    }
    if ($leaveRequestsCount > 0) {
        $usedLeaves = $leaveRequestsCount;
    }

    if ($attendances == 'no_periods') {
        return 'no_periods';
    }

    // $balanceDays = round($totalAttendanceDays / 7);
    // dd($absentDates, $totalAttendanceDays, $balanceDays);
    $absentDays = count($absentDates);
    // Final results to return
    $results = [
        'employee' => Employee::find($employeeId)->name,
        'remaining_leaves' => 0,        // Remaining leave days after accounting for used leave and absences
        'compensated_days' => 0,       // Days to be compensated (unused leave days)
        'excess_absence_days' => 0,    // Days of absence exceeding allowed leave
        'allowed_leaves' => $allowedLeaves,
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
        }
    }
    // Return the final results as an array
    return $results;
}

function calculateAutoWeeklyLeaveDataForBranch_($yearAndMonth, $branchId)
{
    $branchResults = [];
    foreach (
        Employee::where('branch_id', $branchId)
            ->active()->select('id')->get(['id']) as $employee
    ) {
        $results = calculateAutoWeeklyLeaveData($yearAndMonth, $employee->id);
        if ($results != 'no_periods') {
            $branchResults[$employee->id] = $results;
        }
    }
    return $branchResults;
}
function calculateAutoWeeklyLeaveDataForBranch($yearAndMonth, $branchId)
{
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
                }
            }

            $branchResults[$employeeId] = $results;
        }
    }

    return $branchResults;
}

function makeLeavesApplicationsBasedOnBranch($data, $yearMonth, $branchId)
{
    $date = new \DateTime($yearMonth . '-01');
    $year = $date->format('Y');
    $month = $date->format('m');

    $leaveTypeId = LeaveType::where('active', 1)->where('type', LeaveType::TYPE_WEEKLY)
        ->where('balance_period', LeaveType::BALANCE_PERIOD_MONTHLY)
        ->first()?->id;
    foreach ($data as $employeeId => $value) {
        $leaveBalance = LeaveBalance::where('employee_id', $employeeId)
            ->where('year', $year)
            ->where('month', $month)
            ->where('leave_type_id', $leaveTypeId)
            ->first();
        $allowedLeaves = $value['allowed_leaves'];
        $absentDates = $value['absent_dates'];
        checkForMonthlyBalanceAndCreateToCancelAbsent($employeeId, $branchId, $year, $month, $allowedLeaves, $leaveTypeId, $absentDates, $leaveBalance);
    }
    return $data;
}
