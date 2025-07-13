<?php
namespace App\Http\Controllers\Api\HR;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Services\HR\AttendanceHelpers\EmployeePeriodHistoryService;
use App\Services\HR\AttendanceHelpers\Reports\AttendanceFetcher;
use App\Services\HR\AttendanceHelpers\Reports\EmployeesAttendanceOnDateService;
use App\Services\HR\Attendance\AttendanceService;
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
                'id'     => $employee->id,
                'name'   => $employee->name,
                'number' => $employee->employee_no,
            ],
            'from'     => $from->toDateString(),
            'to'       => $to->toDateString(),
            'data'     => $result,
        ]);
    }

    /**
     * API: استرجاع تقرير حضور عدة موظفين ليوم واحد
     * GET /api/employees-attendance-on-date?employee_ids[]=1&employee_ids[]=2&date=2025-07-12
     */
    public function employeesAttendanceOnDate(Request $request, EmployeesAttendanceOnDateService $attendanceService)
    {
        $request->validate([
            'employee_ids'   => 'required|array|min:1',
            'employee_ids.*' => 'required|exists:hr_employees,id',
            'date'           => 'required|date',
        ]);

        $employeeIds = $request->employee_ids;
        $date        = $request->date;

        // استدعاء الخدمة الجديدة
        $reports = $attendanceService->fetchAttendances($employeeIds, $date);

        return response()->json([
            'success'   => true,
            'date'      => $date,
            'employees' => $reports->values(), // لو تريد بدون المفاتيح الرقمية ضع ->values()
        ]);
    }

    public function employeesAttendanceOnDateToTest(Request $request)
    {
        $request->validate([
            'date'  => 'required|date',
            // اختياري: عدد الموظفين للتجربة
            'count' => 'sometimes|integer|min:1',
        ]);

        $date  = $request->input('date');
        $count = $request->input('count', 5000); // الافتراضي 5000

        // جلب IDs أول عدد محدد من الموظفين (active فقط كمثال)
        $employeeIds = \App\Models\Employee::where('active', 1)->limit($count)->pluck('id')->toArray();
        $startTime   = microtime(true);
        $empName     = [];
        $allEmpNames = [];
        $chunkSize   = 1000;

        foreach (array_chunk($employeeIds, $chunkSize) as $chunk) {
            $empNames = Employee::whereIn('id', $chunk)->pluck('name', 'id')->toArray();
            $allEmpNames += $empNames;
        }

        // $attendanceFetcher = new AttendanceFetcher(new EmployeePeriodHistoryService());
        // $attendanceService = new EmployeesAttendanceOnDateService($attendanceFetcher);

        // $results   = $attendanceService->fetchAttendances($employeeIds, $date);
        $endTime = microtime(true);

        $duration = round($endTime - $startTime, 3); // بالثواني

        return response()->json([
            'success'          => true,
            'date'             => $date,
            'count'            => count($employeeIds),
            'duration_seconds' => $duration,
            'names_sample'     => array_slice($empNames, 0, 5), // فقط عينة للعرض

            // 'data'             => $results, // يفضل في الاختبار فقط. في الإنتاج ارجع مختصرًا أو paginated
        ]);
    }
}