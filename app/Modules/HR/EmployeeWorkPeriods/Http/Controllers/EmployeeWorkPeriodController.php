<?php

namespace App\Modules\HR\EmployeeWorkPeriods\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\EmployeePeriod;
use App\Models\WorkPeriod;
use App\Modules\HR\EmployeeWorkPeriods\EmployeeWorkPeriodService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class EmployeeWorkPeriodController extends Controller
{
    public function __construct(protected EmployeeWorkPeriodService $service) {}

    /**
     * Get employee periods.
     *
     * GET /hr/employees/{employee}/work-periods
     *
     * @param Employee $employee
     * @return JsonResponse
     */
    public function index(Employee $employee): JsonResponse
    {
        try {
            $periods = $this->service->getEmployeePeriods($employee->id);

            return response()->json([
                'status' => true,
                'data'   => $periods->map(function ($period) {
                    return [
                        'id'           => $period->id,
                        'period_id'    => $period->period_id,
                        'period_name'  => $period->workPeriod?->name,
                        'start_at'     => $period->workPeriod?->start_at,
                        'end_at'       => $period->workPeriod?->end_at,
                        'start_date'   => $period->start_date,
                        'end_date'     => $period->end_date,
                        'days'         => $period->days->pluck('day_of_week'),
                    ];
                }),
            ]);
        } catch (Exception $e) {
            return response()->json([
                'status'  => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Assign work periods to an employee.
     *
     * POST /hr/employees/{employee}/work-periods
     *
     * @param Request $request
     * @param Employee $employee
     * @return JsonResponse
     */
    public function store(Request $request, Employee $employee): JsonResponse
    {
        $validDays = ['sun', 'mon', 'tue', 'wed', 'thu', 'fri', 'sat'];

        $validator = Validator::make($request->all(), [
            'periods'      => 'required|array|min:1',
            'periods.*'    => 'required|exists:hr_work_periods,id',
            'start_date'   => 'required|date',
            'end_date'     => 'nullable|date|after_or_equal:start_date',
            'period_days'  => 'required|array|min:1',
            'period_days.*' => 'required|string|in:' . implode(',', $validDays),
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => false,
                'message' => 'Validation Error',
                'errors'  => $validator->errors(),
            ], 422);
        }

        try {
            $this->service->assignPeriodsToEmployee($employee, $validator->validated());

            return response()->json([
                'status'  => true,
                'message' => 'Work periods assigned successfully',
            ]);
        } catch (Exception $e) {
            $statusCode = $e->getCode() == 23000 ? 409 : 500;
            return response()->json([
                'status'  => false,
                'message' => $e->getMessage(),
            ], $statusCode);
        }
    }

    /**
     * Assign additional days to an existing employee period.
     *
     * POST /hr/employee-periods/{employeePeriod}/assign-days
     *
     * @param Request $request
     * @param EmployeePeriod $employeePeriod
     * @return JsonResponse
     */
    public function assignDays(Request $request, EmployeePeriod $employeePeriod): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'days'   => 'required|array|min:1',
            'days.*' => 'required|integer|between:0,6',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => false,
                'message' => 'Validation Error',
                'errors'  => $validator->errors(),
            ], 422);
        }

        try {
            // تأكد من عدم إضافة أيام موجودة مسبقاً
            $existingDays = $employeePeriod->days()->pluck('day_of_week')->toArray();
            $newDays = array_diff($request->input('days'), $existingDays);

            if (empty($newDays)) {
                return response()->json([
                    'status'  => false,
                    'message' => 'All selected days are already assigned to this period',
                ], 422);
            }

            $this->service->assignDaysToEmployeePeriod($employeePeriod, $newDays);

            return response()->json([
                'status'  => true,
                'message' => 'Days assigned successfully',
            ]);
        } catch (Exception $e) {
            return response()->json([
                'status'  => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * End/Delete an employee period.
     *
     * DELETE /hr/employee-periods/{employeePeriod}
     *
     * @param Request $request
     * @param EmployeePeriod $employeePeriod
     * @return JsonResponse
     */
    public function destroy(Request $request, int $employeePeriodId): JsonResponse
    {
        $validator = Validator::make(
            array_merge($request->all(), ['employee_period_id' => $employeePeriodId]),
            [
                'employee_period_id' => 'required|exists:hr_employee_periods,id',
                'end_date' => 'required|date',
            ]
        );

        if ($validator->fails()) {
            return response()->json([
                'status'  => false,
                'message' => 'Validation Error',
                'errors'  => $validator->errors(),
            ], 422);
        }

        try {
            $this->service->endEmployeePeriod($employeePeriodId, $request->input('end_date'));

            return response()->json([
                'status'  => true,
                'message' => 'Employee period ended successfully',
            ]);
        } catch (Exception $e) {
            return response()->json([
                'status'  => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get available work periods (shifts) by branch.
     *
     * GET /hr/workPeriods?branch_id=1
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getWorkPeriods(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'branch_id' => 'required|exists:branches,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => false,
                'message' => 'Validation Error',
                'errors'  => $validator->errors(),
            ], 422);
        }

        try {
            $workPeriods = WorkPeriod::where('branch_id', $request->input('branch_id'))
                ->select('id', 'name', 'start_at', 'end_at', 'day_and_night', 'branch_id')
                ->get();

            return response()->json([
                'status' => true,
                'data'   => $workPeriods,
            ]);
        } catch (Exception $e) {
            return response()->json([
                'status'  => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}
