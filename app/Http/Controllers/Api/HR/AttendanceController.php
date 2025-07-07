<?php
namespace App\Http\Controllers\Api\HR;

use App\Http\Controllers\Controller;
use App\Services\HR\Attendance\AttendanceService;
use Illuminate\Http\Request;

class AttendanceController extends Controller
{
    protected AttendanceService $attendanceService;

    public function __construct(AttendanceService $attendanceService)
    {
        $this->attendanceService = $attendanceService;
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'rfid'      => 'required|string|max:255',
            'date_time' => 'nullable|date',
        ]);

        $result = $this->attendanceService->handle($validated);

        return response()->json([
            'status'  => $result['success'] ? true : false,
            'message' => $result['message'],
            'data'    => $result['data'] ?? '',
        ], $result['success'] ? 200 : 422);
    }
}