<?php

namespace App\Services\HR\Dashboard;

use App\DTOs\HR\Dashboard\DashboardFilterDTO;
use App\Models\AppLog;
use App\Models\Attendance;
use App\Models\Branch;
use App\Models\Employee;
use App\Models\EmployeeApplicationV2;
use App\Models\EmployeeOvertime;
use App\Models\ServiceRequest;
use App\Services\HR\AttendanceHelpers\Reports\AbsentEmployeesV2Service;
use App\Services\HR\AttendanceHelpers\Reports\MissingCheckoutService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;


class DashboardService
{
    /**
     * Get pending application and overtime counts
     */
    public function getPendingCounts(DashboardFilterDTO $dto): array
    {
        $baseDate = $dto->dateTime ? $dto->dateTime->clone() : Carbon::now();
        $startOfMonth = $baseDate->clone()->startOfMonth()->toDateString();
        $yesterday = $baseDate->clone()->subDay()->toDateString();

        $missingCheckoutsCount = 0;
        if (Carbon::parse($yesterday)->gte(Carbon::parse($startOfMonth))) {
            $filters = [];
            if ($dto->branchId) {
                $filters['branch_id'] = $dto->branchId;
            }

            $missingCheckoutsCount = app(MissingCheckoutService::class)
                ->getMissingCheckouts($startOfMonth, $yesterday, $filters)
                ->count();
        }

        // Applications
        $appQuery = EmployeeApplicationV2::where('status', EmployeeApplicationV2::STATUS_PENDING)
            ->forBranchManager()
            ->forEmployee();

        if ($dto->branchId) {
            $appQuery->where('branch_id', $dto->branchId);
        }

        $appCounts = $appQuery->select('application_type_id', DB::raw('count(*) as count'))
            ->groupBy('application_type_id')
            ->pluck('count', 'application_type_id');

        // Overtime
        $overtimeQuery = EmployeeOvertime::where('status', EmployeeOvertime::STATUS_PENDING)
            ->forBranchManager()
            ->forEmployee();
        if ($dto->branchId) {
            $overtimeQuery->where('branch_id', $dto->branchId);
        }

        $pendingOvertimeCount = $overtimeQuery->count();

        // New Service Requests
        $srQuery = ServiceRequest::where('status', ServiceRequest::STATUS_NEW);
        if ($dto->branchId) {
            $srQuery->where('branch_id', $dto->branchId);
        }
        $newServiceRequestsCount = $srQuery->count();

        return [
            'pending_leaves'   => $appCounts->get(EmployeeApplicationV2::APPLICATION_TYPE_LEAVE_REQUEST, 0),
            'pending_checkin'  => $appCounts->get(EmployeeApplicationV2::APPLICATION_TYPE_ATTENDANCE_FINGERPRINT_REQUEST, 0),
            'pending_checkout' => $appCounts->get(EmployeeApplicationV2::APPLICATION_TYPE_DEPARTURE_FINGERPRINT_REQUEST, 0),
            'pending_overtime' => $pendingOvertimeCount,
            'pending_advance'  => $appCounts->get(EmployeeApplicationV2::APPLICATION_TYPE_ADVANCE_REQUEST, 0),
            'pending_meal'     => $appCounts->get(EmployeeApplicationV2::APPLICATION_TYPE_MEAL_REQUEST, 0),
            'new_service_request' => $newServiceRequestsCount,
            'missing_checkouts_count' => $missingCheckoutsCount,
            'absents_count'           => $this->getTodayAbsentsCount($dto),
        ];
    }

    /**
     * Get attendance summary for today and the last 7 days by branch
     */
    public function getAttendanceSummaries(DashboardFilterDTO $dto): array
    {
        $branchesQuery = Branch::where('active', 1)->where('is_hidden', 0)
            ->forBranchManager('id')
            ->forEmployee('id');
        if ($dto->branchId) {
            $branchesQuery->where('id', $dto->branchId);
        }
        $branches = $branchesQuery->get(['id', 'name']);

        $baseDate = $dto->dateTime ? $dto->dateTime->clone() : Carbon::now();
        $today = $baseDate->toDateString();

        $todayAttendance = [];
        $last7DaysAttendance = [];

        // Last 7 days including the selected date
        $dates = [];
        for ($i = 6; $i >= 0; $i--) {
            // we use toDateString() to just generate the string label for the day
            $dates[] = $baseDate->clone()->subDays($i)->toDateString();
        }

        // Fetch all active employees counts grouped by branch
        $employeeCounts = Employee::where('active', 1)
            ->when($dto->branchId, fn($q) => $q->where('branch_id', $dto->branchId))
            ->select('branch_id', DB::raw('count(*) as count'))
            ->groupBy('branch_id')
            ->pluck('count', 'branch_id');

        // Fetch all relevant attendance records for the last 7 days for all relevant branches
        $allAttendances = Attendance::whereIn('check_date', $dates)
            ->where('check_type', Attendance::CHECKTYPE_CHECKIN)
            ->where('accepted', 1)
            ->when($dto->branchId, fn($q) => $q->where('branch_id', $dto->branchId))
            ->select('branch_id', 'check_date', 'employee_id', 'status')
            ->get()
            ->groupBy(['branch_id', 'check_date']);

        // Fetch live present employees using the dedicated service to properly handle overnight shifts
        $presentService = app(\App\Services\HR\AttendanceHelpers\Reports\PresentEmployeesService::class);
        $livePresentEmployees = $presentService->getPresentEmployees($baseDate, $dto->branchId ? ['branch_id' => $dto->branchId] : []);
        $livePresentGrouped = $livePresentEmployees->groupBy('branchId');

        // Pre-fetch today's real absent count per branch via AbsentEmployeesV2Service
        // (accounts for shifts, approved leaves, holidays — not just total - present)
        $absentFilters = [];
        if ($dto->branchId) {
            $absentFilters['branch_id'] = $dto->branchId;
        }
        $absentFilters['current_time'] = $baseDate->format('H:i:s');

        $absentEmployeesToday = app(AbsentEmployeesV2Service::class)
            ->getAbsentEmployees($today, $today, $absentFilters);

        AppLog::write(
            'absent_employees_today',
            AppLog::LEVEL_INFO,
            'DashboardService',
            [
                'base_date' => $baseDate->format('Y-m-d H:i:s'),
                'absent_employees_today' => $absentEmployeesToday,
                'absent_filters' => $absentFilters,
                'today' => $today,
                'baseDate' => $baseDate,
                'dto' => $dto,
            ]
        );
        // Group absent count by branch_id for O(1) lookups inside the loop
        $absentCountByBranch = $absentEmployeesToday
            ->groupBy('branch_id')
            ->map(fn($group) => $group->count());

        foreach ($branches as $branch) {
            $bId = $branch->id;
            $totalEmployees = $employeeCounts->get($bId, 0);

            // -- Today's Attendance (Live Present) --
            $branchDayData = $allAttendances->get($bId, collect());

            $branchLivePresent = $livePresentGrouped->get($bId, collect());
            $todayPresent = $branchLivePresent->count();
            $todayLate = $branchLivePresent->where('status', Attendance::STATUS_LATE_ARRIVAL)->count();
            $todayAbsent = $absentCountByBranch->get($bId, 0);
            $todayPercentage = $totalEmployees > 0 ? round(($todayPresent / $totalEmployees) * 100, 2) : 0;

            $todayAttendance[] = [
                'branch_id'   => $bId,
                'branch_name' => $branch->name,
                'total'       => $totalEmployees,
                'present'     => $todayPresent,
                'late'        => $todayLate,
                'absent'      => $todayAbsent,
                'percentage'  => $todayPercentage,
            ];

            // -- Last 7 Days Attendance --
            $history = [];
            foreach ($dates as $date) {
                if ($date === $today) {
                    $presentCount = $todayPresent;
                    $lateCount    = $todayLate;
                    $absentCount  = $todayAbsent;
                    $percentage   = $todayPercentage;
                } else {
                    $dayData = $branchDayData->get($date, collect());

                    $presentCount = $dayData->unique('employee_id')->count();
                    $lateCount    = $dayData->where('status', Attendance::STATUS_LATE_ARRIVAL)->unique('employee_id')->count();
                    $absentCount  = max(0, $totalEmployees - $presentCount);
                    $percentage   = $totalEmployees > 0 ? round(($presentCount / $totalEmployees) * 100, 2) : 0;
                }

                $history[] = [
                    'date'       => $date,
                    'total'      => $totalEmployees,
                    'present'    => $presentCount,
                    'late'       => $lateCount,
                    'absent'     => $absentCount,
                    'percentage' => $percentage,
                ];
            }

            $last7DaysAttendance[] = [
                'branch_id'   => $bId,
                'branch_name' => $branch->name,
                'history'     => $history,
            ];
        }


        return [
            'today_attendance' => $todayAttendance,
            'last_7_days_attendance' => $last7DaysAttendance,
        ];
    }

    /**
     * Get maintenance alerts from Service Requests
     */
    public function getMaintenanceAlerts(DashboardFilterDTO $dto): array
    {
        $stats = ServiceRequest::query()
            ->when($dto->branchId, fn($q) => $q->where('branch_id', $dto->branchId))
            ->selectRaw("
                COUNT(CASE WHEN status != '" . ServiceRequest::STATUS_CLOSED . "' THEN 1 END) as open_count,
                COUNT(CASE WHEN urgency = '" . ServiceRequest::URGENCY_HIGH . "' AND status != '" . ServiceRequest::STATUS_CLOSED . "' THEN 1 END) as high_priority_count,
                COUNT(CASE WHEN status = '" . ServiceRequest::STATUS_IN_PROGRESS . "' THEN 1 END) as in_progress_count
            ")
            ->first();
        // dd($stats);
        return [
            'open'          => (int) ($stats->open_count ?? 0),
            'high_priority' => (int) ($stats->high_priority_count ?? 0),
            'in_progress'   => (int) ($stats->in_progress_count ?? 0),
        ];
    }

    /**
     * احسب عدد الغائبين اليوم باستخدام AbsentEmployeesV2Service
     * للحصول على نتيجة دقيقة تأخذ بعين الاعتبار الورديات والإجازات والعطل.
     */
    private function getTodayAbsentsCount(DashboardFilterDTO $dto): int
    {
        $baseDate = $dto->dateTime ? $dto->dateTime->clone() : Carbon::now();
        $today    = $baseDate->toDateString();

        $filters = [];
        if ($dto->branchId) {
            $filters['branch_id'] = $dto->branchId;
        }
        $filters['current_time'] = $baseDate->format('H:i:s');

        return app(AbsentEmployeesV2Service::class)
            ->getAbsentEmployees($today, $today, $filters)
            ->count();
    }
}
