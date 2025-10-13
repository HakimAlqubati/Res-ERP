<?php

namespace App\Services\HR\Attendance;

use App\Models\Employee;
use Illuminate\Support\Facades\Log;

class AttendancePlanExecutor
{
    protected AttendancePlanService $planService;
    protected AttendanceService $attendanceService;

    public function __construct(
        AttendancePlanService $planService,
        AttendanceService $attendanceService
    ) {
        $this->planService = $planService;
        $this->attendanceService = $attendanceService;
    }

    /**
     * ينفذ خطة الحضور لموظف بين تاريخين باستخدام handleTwoDates
     */
    public function executePlan(int $employeeId, int $workPeriodId, string $fromDate, string $toDate): array
    {
        // 1) توليد الخطة من AttendancePlanService
        $plan = $this->planService->buildPlan($workPeriodId, $fromDate, $toDate);

        $results = [];

        // 2) المرور على كل يوم في الخطة
        foreach ($plan as $day) {
            $formData = [
                'employee_id' => $employeeId,
                'check_in'    => $day['check_in'],
                'check_out'   => $day['check_out'],
            ];

            // استدعاء دالة handleTwoDates من AttendanceService
            $result = $this->attendanceService->handleTwoDates($formData, 'manual');

            $results[] = [
                'date'    => $day['date'],
                'check_in'=> $day['check_in'],
                'check_out'=> $day['check_out'],
                'success' => $result['success'],
                'message' => $result['message'] ?? null,
            ];
        }

        return [
            'success' => true,
            'message' => 'Plan executed successfully.',
            'count'   => count($results),
            'data'    => $results,
        ];
    }
}
