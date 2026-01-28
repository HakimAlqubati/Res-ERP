<?php

namespace App\Modules\HR\Attendance\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\HR\Attendance\Services\AttendanceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Controller لـ API الحضور
 * 
 * يوفر endpoints لتسجيل الحضور والانصراف
 */
class AttendanceController extends Controller
{
    public function __construct(
        private AttendanceService $attendanceService
    ) {}

    /**
     * تسجيل حضور/انصراف
     * 
     * POST /api/v3/hr/attendance
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'rfid' => 'nullable|string|max:255',
            'employee_id' => 'nullable|integer|exists:hr_employees,id',
            'date_time' => 'nullable|date',
            'type' => 'nullable|string|in:checkin,checkout',
            'attendance_type' => 'nullable|string|in:rfid,request,webcam',
            'period_id' => 'nullable|integer|exists:hr_work_periods,id',
        ]);

        // لازم واحد منهم يكون موجود
        if (empty($validated['rfid']) && empty($validated['employee_id'])) {
            return response()->json([
                'success' => false,
                'message' => 'Either rfid or employee_id is required.',
            ], 422);
        }

        $result = $this->attendanceService->handle($validated);

        return $result->toResponse();
    }

    /**
     * اختبار تسجيل الحضور (للتوافق مع V2)
     * 
     * POST /api/v3/hr/attendance/test
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function test(Request $request): JsonResponse
    {
        $result = $this->attendanceService->handle($request->all());
        return response()->json($result->toArray());
    }

    /**
     * توليد سجلات حضور جماعية
     * 
     * POST /api/v2/hr/attendance/bulk-generate
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function bulkGenerate(Request $request): JsonResponse
    {
        $service = app(\App\Modules\HR\Attendance\Services\BulkAttendanceGeneratorService::class);
        return response()->json($service->generate($request->all()));
    }
}
