<?php

namespace App\Http\Controllers\API\HR;

use App\Http\Controllers\Controller;
use App\Services\HR\Payroll\PayrollSimulationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PayrollSimulationController extends Controller
{
    protected PayrollSimulationService $simulationService;

    public function __construct(PayrollSimulationService $simulationService)
    {
        $this->simulationService = $simulationService;
    }

    /**
     * محاكاة الرواتب لمجموعة موظفين
     */
    public function simulateSalariesByEmployeeIds(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'employee_ids'   => 'required|array|min:1',
            'employee_ids.*' => 'integer|exists:hr_employees,id',
            'year'           => 'required|integer|min:2000',
            'month'          => 'required|integer|between:1,12',
        ]);

        $results = $this->simulationService->simulateForEmployees(
            $validated['employee_ids'],
            $validated['year'],
            $validated['month']
        );

        return response()->json([
            'success' => true,
            'message' => 'Salary simulation completed.',
            'data'    => $results,
        ]);
    }
}
