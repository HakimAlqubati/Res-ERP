<?php

namespace App\Http\Controllers\API\HR;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Services\HR\Payroll\PayrollBatchHandler;
use App\Services\HR\Payroll\PayrollCalculationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PayrollCalculationController extends Controller
{
    protected PayrollCalculationService $payrollService;
    protected PayrollBatchHandler $batchHandler;

    public function __construct(PayrollCalculationService $payrollService)
    {
        $this->payrollService = $payrollService;
        $this->batchHandler   = new PayrollBatchHandler($payrollService);
    }

    /**
     * حساب راتب موظف بناء على بيانات الحضور
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function calculateSalary(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'employee_id' => 'required|exists:hr_employees,id',
            'year'        => 'required|integer|min:2000',
            'month'       => 'required|integer|between:1,12',
        ]);

        $employee = Employee::findOrFail($validated['employee_id']);

        $payroll = $this->payrollService->calculateForEmployee(
            $employee,
            $validated['year'],
            $validated['month']
        );

        return response()->json($payroll); 
    }

     /**
     * حساب رواتب مجموعة موظفين بناءً على قائمة IDs
     */
    public function calculateSalariesByEmployeeIds(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'employee_ids' => 'required|array|min:1',
            'employee_ids.*' => 'integer|exists:hr_employees,id',
            'year' => 'required|integer|min:2000',
            'month' => 'required|integer|between:1,12',
        ]);

        $result = $this->batchHandler->handleByEmployeeIds(
            $validated['employee_ids'],
            $validated['year'],
            $validated['month']
        );

        return response()->json([
            'success' => true,
            'message' => 'Payroll calculation completed for selected employees.',
            'data' => $result
        ]);
    }

    /**
     * حساب رواتب موظفي فرع معين
     */
    public function calculateSalariesByBranch(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'branch_id' => 'required|integer|exists:branches,id',
            'year'      => 'required|integer|min:2000',
            'month'     => 'required|integer|between:1,12',
        ]);

        $result = $this->batchHandler->handleByBranch(
            $validated['branch_id'],
            $validated['year'],
            $validated['month']
        );

        return response()->json([
            'success' => true,
            'message' => 'Payroll calculation completed for branch employees.',
            'data' => $result
        ]);
    }
}
