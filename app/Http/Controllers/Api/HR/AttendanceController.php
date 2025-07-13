<?php
namespace App\Http\Controllers\Api\HR;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Services\HR\Attendance\AttendanceService;
use App\Services\HR\AttendanceHelpers\EmployeePeriodHistoryService;
use App\Services\HR\AttendanceHelpers\Reports\AttendanceFetcher;
use Carbon\Carbon;
use Illuminate\Http\Request;

class AttendanceController extends Controller
{
    protected AttendanceService $attendanceService;
    protected $attendanceFetcher;

    public function __construct(AttendanceService $attendanceService)
    {
        $this->attendanceService = $attendanceService;
        $this->attendanceFetcher = new AttendanceFetcher(new EmployeePeriodHistoryService());

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

    
    /**
     * API: استرجاع سجل الحضور لموظف محدد خلال مدى زمني
     * GET /api/employee-attendance?employee_id=1&from=2024-07-01&to=2024-07-31
     */
    public function employeeAttendance(Request $request)
    {
        $request->validate([
            'employee_id' => 'required|exists:hr_employees,id',
            'from'        => 'required|date',
            'to'          => 'required|date|after_or_equal:from',
        ]);

        $employee = Employee::findOrFail($request->employee_id);

        $from = Carbon::parse($request->from);
        $to   = Carbon::parse($request->to);

        $result = $this->attendanceFetcher->fetchEmployeeAttendances($employee, $from, $to);

        // إذا أردت تنسيق البيانات قبل الإرجاع يمكنك تعديل النتائج هنا

        return response()->json([
            'success'  => true,
            'employee' => [
                'id'   => $employee->id,
                'name' => $employee->name,
                'number' => $employee->employee_no,
            ],
            'from'     => $from->toDateString(),
            'to'       => $to->toDateString(),
            'data'     => $result,
        ]);
    }
}