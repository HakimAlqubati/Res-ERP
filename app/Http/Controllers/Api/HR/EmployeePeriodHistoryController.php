<?php
namespace App\Http\Controllers\Api\HR;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Services\HR\AttendanceHelpers\EmployeePeriodHistoryService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class EmployeePeriodHistoryController extends Controller
{
    protected EmployeePeriodHistoryService $historyService;

    public function __construct(EmployeePeriodHistoryService $historyService)
    {
        $this->historyService = $historyService;
    }

    public function getPeriodsByDateRange(Request $request, $employeeId)
    {
        $request->validate([
            'start_date' => 'required|date',
            'end_date'   => 'required|date|after_or_equal:start_date',
        ]);

        $employee = Employee::findOrFail($employeeId);

        $start = Carbon::parse($request->input('start_date'));
        $end   = Carbon::parse($request->input('end_date'));

        $result = $this->historyService->getEmployeePeriodsByDateRange($employee, $start, $end);

        return response()->json([
            'employee_id'   => $employee->id,
            'employee_name' => $employee->name,
            'range'         => [
                'start' => $start->toDateString(),
                'end'   => $end->toDateString(),
            ],
            'data'          => $result,
        ]);
    }
}