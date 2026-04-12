<?php

namespace App\Modules\HR\AttendanceReports\Data;

use App\Models\EmployeePeriodHistory;
use App\Models\Attendance;
use App\Models\EmployeeServiceTermination;
use App\Models\EmployeeOvertime;
use App\Models\WorkPeriod;
use Illuminate\Support\Facades\DB;

/**
 * Class AttendanceDataFetcher
 * 
 * Responsible for handling all database interactions for the Attendance V2 module.
 * It strictly performs optimized, eager-loaded queries to fetch histories, branches,
 * attendances, leaves, and overtimes to prevent N+1 query performance issues.
 */
class AttendanceDataFetcher
{
    /**
     * Fetch attendance-related data for multiple employees on a single specific date.
     * 
     * @param array $employeeIds Array of target employee IDs.
     * @param string $dateStr The target date in 'Y-m-d' format.
     * @return array An associative array containing collections of 'histories', 'attendances', 
     *               'leaves', 'terminations', 'overtimes', and 'workPeriodMap' indexed optimally.
     */
    public function fetchForMultiEmployeesSingleDate(array $employeeIds, string $dateStr): array
    {
        $histories = EmployeePeriodHistory::with(['workPeriod', 'branch'])
            ->where('active', 1)
            ->whereIn('employee_id', $employeeIds)
            ->where('start_date', '<=', $dateStr)
            ->where(function ($q) use ($dateStr) {
                $q->whereNull('end_date')->orWhere('end_date', '>=', $dateStr);
            })
            ->get();

        $attendances = Attendance::with('branch')
            ->where('deleted_at', null)
            ->where('accepted', 1)
            ->whereIn('employee_id', $employeeIds)
            ->where('check_date', $dateStr)
            ->orderBy('id')
            ->get()
            ->groupBy('employee_id');

        $leaves = DB::table('hr_employee_applications')
            ->join('hr_leave_requests', 'hr_employee_applications.id', '=', 'hr_leave_requests.application_id')
            ->join('hr_leave_types', 'hr_leave_requests.leave_type', '=', 'hr_leave_types.id')
            ->where('hr_employee_applications.application_type_id', 1)
            ->where('hr_employee_applications.status', 'approved')
            ->whereIn('hr_employee_applications.employee_id', $employeeIds)
            ->where('hr_leave_requests.start_date', '<=', $dateStr)
            ->where('hr_leave_requests.end_date', '>=', $dateStr)
            ->select(
                'hr_employee_applications.employee_id',
                'hr_leave_requests.start_date as from_date',
                'hr_leave_requests.end_date as to_date',
                'hr_leave_requests.leave_type',
                'hr_leave_types.name as transaction_description'
            )
            ->get()
            ->keyBy('employee_id');

        $terminations = DB::table('hr_employee_service_terminations')
            ->whereIn('employee_id', $employeeIds)
            ->where('status', EmployeeServiceTermination::STATUS_APPROVED)
            ->where('termination_date', '<', $dateStr)
            ->pluck('termination_date', 'employee_id');

        $overtimes = EmployeeOvertime::whereIn('employee_id', $employeeIds)
            ->where('status', EmployeeOvertime::STATUS_APPROVED)
            ->day()
            ->where('date', $dateStr)
            ->select('employee_id', 'hours', 'date')
            ->get()
            ->groupBy('employee_id');

        $workPeriodIds = $histories->pluck('period_id')->unique()->toArray();
        $workPeriodMap = WorkPeriod::whereIn('id', $workPeriodIds)
            ->get(['id', 'name', 'start_at', 'end_at', 'day_and_night'])
            ->keyBy('id');

        return compact('histories', 'attendances', 'leaves', 'terminations', 'overtimes', 'workPeriodMap');
    }

    /**
     * Fetch attendance-related data for a single employee spanning a specific date range.
     * 
     * @param int $employeeId The target employee ID.
     * @param string $startDateStr The start date in 'Y-m-d' format.
     * @param string $endDateStr The end date in 'Y-m-d' format.
     * @return array An associative array containing deeply nested collections of 'histories',
     *               'attendances', 'leaves', 'terminations', 'overtimes', and 'workPeriodMap'.
     */
    public function fetchForSingleEmployeeRange(int $employeeId, string $startDateStr, string $endDateStr): array
    {
        $histories = EmployeePeriodHistory::with(['workPeriod', 'branch'])
            ->where('active', 1)
            ->where('employee_id', $employeeId)
            ->where('start_date', '<=', $endDateStr)
            ->where(function ($q) use ($startDateStr) {
                $q->whereNull('end_date')->orWhere('end_date', '>=', $startDateStr);
            })
            ->get();

        $attendances = Attendance::with('branch')
            ->where('deleted_at', null)
            ->where('accepted', 1)
            ->where('employee_id', $employeeId)
            ->whereBetween('check_date', [$startDateStr, $endDateStr])
            ->orderBy('id')
            ->get()
            ->groupBy('check_date');

        $leaves = DB::table('hr_employee_applications')
            ->join('hr_leave_requests', 'hr_employee_applications.id', '=', 'hr_leave_requests.application_id')
            ->join('hr_leave_types', 'hr_leave_requests.leave_type', '=', 'hr_leave_types.id')
            ->where('hr_employee_applications.application_type_id', 1)
            ->where('hr_employee_applications.status', 'approved')
            ->where('hr_employee_applications.employee_id', $employeeId)
            ->where(function ($q) use ($startDateStr, $endDateStr) {
                $q->where('hr_leave_requests.start_date', '<=', $endDateStr)
                    ->where('hr_leave_requests.end_date', '>=', $startDateStr);
            })
            ->select(
                'hr_leave_requests.start_date as from_date',
                'hr_leave_requests.end_date as to_date',
                'hr_leave_requests.leave_type',
                'hr_leave_types.name as transaction_description'
            )
            ->get();

        $terminations = DB::table('hr_employee_service_terminations')
            ->where('employee_id', $employeeId)
            ->where('status', EmployeeServiceTermination::STATUS_APPROVED)
            ->first();

        $overtimes = EmployeeOvertime::where('employee_id', $employeeId)
            ->where('status', EmployeeOvertime::STATUS_APPROVED)
            ->day()
            ->whereBetween('date', [$startDateStr, $endDateStr])
            ->select('hours', 'date')
            ->get()
            ->groupBy('date');

        $workPeriodIds = $histories->pluck('period_id')->unique()->toArray();
        $workPeriodMap = WorkPeriod::whereIn('id', $workPeriodIds)
            ->get(['id', 'name', 'start_at', 'end_at', 'day_and_night'])
            ->keyBy('id');

        return compact('histories', 'attendances', 'leaves', 'terminations', 'overtimes', 'workPeriodMap');
    }

    /**
     * Fetch attendance-related data for multiple employees spanning a specific date range.
     * 
     * @param array $employeeIds The target employee IDs.
     * @param string $startDateStr The start date in 'Y-m-d' format.
     * @param string $endDateStr The end date in 'Y-m-d' format.
     * @return array An associative array containing collections grouped by employee_id.
     */
    public function fetchForMultiEmployeesRange(array $employeeIds, string $startDateStr, string $endDateStr): array
    {
        $histories = EmployeePeriodHistory::with(['workPeriod', 'branch'])
            ->where('active', 1)
            ->whereIn('employee_id', $employeeIds)
            ->where('start_date', '<=', $endDateStr)
            ->where(function ($q) use ($startDateStr) {
                $q->whereNull('end_date')->orWhere('end_date', '>=', $startDateStr);
            })
            ->get()
            ->groupBy('employee_id');

        $attendances = Attendance::with('branch')
            ->where('deleted_at', null)
            ->where('accepted', 1)
            ->whereIn('employee_id', $employeeIds)
            ->whereBetween('check_date', [$startDateStr, $endDateStr])
            ->orderBy('id')
            ->get()
            ->groupBy(['employee_id', 'check_date']);

        $leaves = DB::table('hr_employee_applications')
            ->join('hr_leave_requests', 'hr_employee_applications.id', '=', 'hr_leave_requests.application_id')
            ->join('hr_leave_types', 'hr_leave_requests.leave_type', '=', 'hr_leave_types.id')
            ->where('hr_employee_applications.application_type_id', 1)
            ->where('hr_employee_applications.status', 'approved')
            ->whereIn('hr_employee_applications.employee_id', $employeeIds)
            ->where(function ($q) use ($startDateStr, $endDateStr) {
                $q->where('hr_leave_requests.start_date', '<=', $endDateStr)
                    ->where('hr_leave_requests.end_date', '>=', $startDateStr);
            })
            ->select(
                'hr_employee_applications.employee_id',
                'hr_leave_requests.start_date as from_date',
                'hr_leave_requests.end_date as to_date',
                'hr_leave_requests.leave_type',
                'hr_leave_types.name as transaction_description'
            )
            ->get()
            ->groupBy('employee_id');

        $terminations = DB::table('hr_employee_service_terminations')
            ->whereIn('employee_id', $employeeIds)
            ->where('status', EmployeeServiceTermination::STATUS_APPROVED)
            ->get()
            ->keyBy('employee_id');

        $overtimes = EmployeeOvertime::whereIn('employee_id', $employeeIds)
            ->where('status', EmployeeOvertime::STATUS_APPROVED)
            ->day()
            ->whereBetween('date', [$startDateStr, $endDateStr])
            ->select('employee_id', 'hours', 'date')
            ->get()
            ->groupBy(['employee_id', 'date']);

        $allPeriodIds = collect();
        foreach ($histories as $empHistories) {
            $allPeriodIds = $allPeriodIds->merge($empHistories->pluck('period_id'));
        }
        $workPeriodIds = $allPeriodIds->unique()->toArray();

        $workPeriodMap = WorkPeriod::whereIn('id', $workPeriodIds)
            ->get(['id', 'name', 'start_at', 'end_at', 'day_and_night'])
            ->keyBy('id');

        return compact('histories', 'attendances', 'leaves', 'terminations', 'overtimes', 'workPeriodMap');
    }


    public function getEmployeePeriodAttendnaceDetails($employeeId, $periodId, $date)
    {
        $attenance = Attendance::where('employee_id', $employeeId)
            ->accepted()
            ->where('period_id', $periodId)
            ->where('check_date', $date)
            ->select('check_time', 'check_type', 'period_id')
            ->orderBy('id', 'asc')
            // ->groupBy('period_id')
            ->get();
        return $attenance;
    }
}
