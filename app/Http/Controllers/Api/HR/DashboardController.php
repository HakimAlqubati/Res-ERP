<?php

namespace App\Http\Controllers\Api\HR;

use App\Http\Controllers\Controller;
use App\DTOs\HR\Dashboard\DashboardFilterDTO;
use App\Services\HR\Dashboard\DashboardService;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __construct(
        protected DashboardService $dashboardService
    ) {}

    /**
     * Get HR Dashboard Summary
     * - Pending requests counts
     * - Today's Staff Attendance by branch
     * - Last 7 days Attendance by branch
     */
    public function index(Request $request)
    {
        $dto = DashboardFilterDTO::fromRequest($request);

        $pendingCounts = $this->dashboardService->getPendingCounts($dto);
        $attendanceData = $this->dashboardService->getAttendanceSummaries($dto);

        return response()->json([
            'success' => true,
            'data' => [
                'pending_requests'       => $pendingCounts,
                'today_attendance'       => $attendanceData['today_attendance'],
                'last_7_days_attendance' => $attendanceData['last_7_days_attendance'],
            ]
        ]);
    }
}
