<?php

namespace App\Modules\HR\Overtime\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\EmployeeOvertime;
use App\Modules\HR\Overtime\OvertimeService;
use Exception;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class OvertimeController extends Controller
{
    public function __construct(protected OvertimeService $overtimeService) {}

    /**
     * Get list of overtime records
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        try {
            $overtime = $this->overtimeService->getOvertime($request->all());
            return response()->json([
                'status' => true,
                'data'   => $overtime,
            ]);
        } catch (Exception $e) {
            return response()->json([
                'status'  => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Store new overtime record (handles single or bulk)
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    /**
     * Store new overtime record
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        // Define validation rules
        $rules = [
            'type' => 'required|in:' . implode(',', EmployeeOvertime::TYPES),
            'date' => 'required|date',
            'branch_id' => 'required|exists:branches,id',
            'employees' => 'required|array|min:1',
            'employees.*.employee_id' => 'required|exists:hr_employees,id',
            'employees.*.start_time' => 'required|date_format:H:i',
            'employees.*.end_time' => 'required|date_format:H:i|after:hr_employees.*.start_time',
            'employees.*.hours' => 'required|numeric|min:0',
            'employees.*.notes' => 'nullable|string',
        ];

        // Validate the request
        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation Error',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $data = $validator->validated();

            // Check for existing overtime records before inserting
            $employeeIds = collect($data['employees'])->pluck('employee_id');
            $existingRecords = EmployeeOvertime::where('date', $data['date'])
                ->where('type', $data['type'])
                ->whereIn('employee_id', $employeeIds)
                ->with('employee:id,name')
                ->get();

            if ($existingRecords->isNotEmpty()) {
                $duplicateNames = $existingRecords->map(function ($record) {
                    return $record->employee
                        ? "{$record->employee->name} (#{$record->employee_id})"
                        : "#{$record->employee_id}";
                })->implode(', ');

                return response()->json([
                    'status'  => false,
                    'message' => "Overtime already exists for the following employees on {$data['date']}: {$duplicateNames}",
                ], 422);
            }

            if ($data['type'] === EmployeeOvertime::TYPE_BASED_ON_DAY) {
                $this->overtimeService->handleOvertimeByDay($data);
            } elseif ($data['type'] === EmployeeOvertime::TYPE_BASED_ON_MONTH) {
                $this->overtimeService->handleOverTimeMonth($data);
            }

            return response()->json([
                'status'  => true,
                'message' => 'Overtime records created successfully',
            ]);
        } catch (QueryException $e) {
            if ($e->errorInfo[1] == 1062) {
                return response()->json([
                    'status'  => false,
                    'message' => 'Overtime record already exists for one or more employees on this date.',
                ], 422);
            }

            return response()->json([
                'status'  => false,
                'message' => 'A database error occurred.',
            ], 500);
        } catch (Exception $e) {
            return response()->json([
                'status'  => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Approve overtime (Bulk)
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function approve(Request $request)
    {
        try {
            $ids = $request->input('ids');
            if (empty($ids) || !is_array($ids)) {
                return response()->json([
                    'status' => false,
                    'message' => 'ids array is required'
                ], 422);
            }

            $overtime = $this->overtimeService->approve($ids);
            return response()->json([
                'status'  => true,
                'message' => 'Overtime approved successfully',
                'data'    => $overtime,
            ]);
        } catch (Exception $e) {
            return response()->json([
                'status'  => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Undo overtime approval (Bulk)
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function undoApproval(Request $request)
    {
        try {
            $ids = $request->input('ids');
            if (empty($ids) || !is_array($ids)) {
                return response()->json([
                    'status' => false,
                    'message' => 'ids array is required'
                ], 422);
            }

            $overtime = $this->overtimeService->undoApproval($ids);
            return response()->json([
                'status'  => true,
                'message' => 'Overtime approval undone successfully',
                'data'    => $overtime,
            ]);
        } catch (Exception $e) {
            return response()->json([
                'status'  => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get suggested overtime for employees
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getSuggestedOvertime(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'date'      => 'required|date',
                'branch_id' => 'required|exists:branches,id',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Validation Error',
                    'errors' => $validator->errors()
                ], 422);
            }

            $data = $this->overtimeService->getSuggestedOvertime(
                $request->input('date'),
                $request->input('branch_id')
            );

            return response()->json([
                'status' => true,
                'data'   => $data,
            ]);
        } catch (Exception $e) {
            return response()->json([
                'status'  => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get overtime report with filters and summary.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function report(Request $request)
    {
        try {
            $filter = \App\Modules\HR\Overtime\Reports\DTOs\OvertimeReportFilter::fromArray($request->all());
            $report = app(\App\Modules\HR\Overtime\Reports\OvertimeReportService::class)->generate($filter);

            // Fetch paginated items and merge summary into the response
            $paginator = $report['items']->toArray();

            return response()->json(array_merge(
                ['status' => true],
                $paginator, // This includes: data, current_page, last_page, per_page, total, etc.
                ['summary' => $report['summary']]
            ));
        } catch (Exception $e) {
            return response()->json([
                'status'  => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}
