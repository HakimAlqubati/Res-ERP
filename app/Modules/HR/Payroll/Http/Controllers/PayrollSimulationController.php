<?php

namespace App\Modules\HR\Payroll\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\PayrollRun;
use App\Modules\HR\Payroll\Services\PayrollSimulationService;
use Carbon\Carbon;
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

    /**
     * Run-aware salary simulation for a set of employees (no DB writes).
     * Requires PayrollSimulationService::simulateForRunEmployees(PayrollRun $run, array $employeeIds)
     * POST /api/hr/simulation/runs/{run}
     */
    public function simulateByRun(PayrollRun $run, Request $request): JsonResponse
    {
        $validated = $request->validate([
            'employee_ids'   => 'required|array|min:1',
            'employee_ids.*' => 'integer|exists:hr_employees,id',
        ]);

        // Ensure the service has the run-aware method added
        $results = $this->simulationService->simulateForRunEmployees($run, $validated['employee_ids']);

        return response()->json([
            'success' => true,
            'message' => 'Salary simulation (by run) completed.',
            'meta'    => [
                'payroll_run_id' => $run->id,
                'branch_id'      => $run->branch_id,
                'year'           => (int) $run->year,
                'month'          => (int) $run->month,
                'period_start'   => $run->period_start_date,
                'period_end'     => $run->period_end_date,
                'count'          => count($results),
            ],
            'data'    => $results,
        ]);
    }

    /**
     * Preview salary simulation BEFORE creating any PayrollRun (no DB writes).
     * You can pass employee_ids OR leave empty to simulate all active employees in the branch.
     *
     * POST /api/hr/simulation/preview
     * Body:
     * {
     *   "branch_id": 7,
     *   "year": 2025,
     *   "month": 7,
     *   "employee_ids": [1,2,3] // optional
     * }
     */
    public function previewByBranchYearMonth(Request $request): JsonResponse
    {
        try {
            //code...

            $validated = $request->validate([
                'branch_id'      => 'required|integer|exists:branches,id',
                'year'           => 'required|integer|min:2000|max:2100',
                'month'          => 'required|integer|between:1,12',
            ]);

            $branchId = (int) $validated['branch_id'];
            $year     = (int) $validated['year'];
            $month    = (int) $validated['month'];

            // Resolve period from year/month (no writes)
            $periodStart = Carbon::create($year, $month, 1)->startOfMonth()->toDateString();
            $periodEnd   = Carbon::create($year, $month, 1)->endOfMonth()->toDateString();

            // If employee_ids not provided => simulate all active employees in that branch

            // Optional: filter to keep only employees that actually belong to the branch
            $employeeIds = Employee::query()->active()
                ->where('branch_id', $branchId)
                ->pluck('id')
                ->all();


            // Run simulation (no DB writes)
            $results = $this->simulationService->simulateForEmployees($employeeIds, $year, $month);

            // Aggregate quick totals from result payloads
            $totals = [
                'total_gross'      => 0.0,
                'total_net'        => 0.0,
                'total_allowances' => 0.0, // gross - base OR (overtime + allowances if returned)
                'total_deductions' => 0.0,
                'count'            => count($results),
            ];

            foreach ($results as $row) {
                if (!($row['success'] ?? false)) {
                    continue;
                }
                $data = $row['data'] ?? [];
                $totals['total_gross']      += (float)($data['gross_salary']      ?? 0);
                $totals['total_net']        += (float)($data['net_salary']        ?? 0);
                $totals['total_deductions'] += (float)($data['absence_deduction'] ?? 0);

                // Try to approximate allowances = (gross - base). If you return explicit allowances, swap this logic.
                $base     = (float)($data['base_salary'] ?? 0);
                $gross    = (float)($data['gross_salary'] ?? 0);
                $allowEst = max(0, $gross - $base);
                $totals['total_allowances'] += $allowEst;
            }

            // Suggest a run name (without creating it)
            $suggestedName = sprintf('Payroll %04d-%02d (Branch %d)', $year, $month, $branchId);

            // Soft-check: is there an existing run for this branch & period?
            $existingRun = PayrollRun::query()
                ->where('branch_id', $branchId)
                ->where('year', $year)
                ->where('month', $month)
                ->first(['id', 'status', 'name']);

            $warnings = [];
            if ($existingRun) {
                $warnings[] = "A PayrollRun already exists for this branch/period (ID={$existingRun->id}, status={$existingRun->status}). Simulation remains read-only.";
            }
            if (empty($employeeIds)) {
                $warnings[] = "No eligible employees found for the given branch/filters.";
            }

            return response()->json([
                'success' => true,
                'message' => 'Salary preview simulation completed (no DB writes).',
                'meta'    => [
                    'branch_id'    => $branchId,
                    'year'         => $year,
                    'month'        => $month,
                    'period_start' => $periodStart,
                    'period_end'   => $periodEnd,
                    'suggested_run_name' => $suggestedName,
                    'existing_run' => $existingRun ? [
                        'id'     => $existingRun->id,
                        'status' => $existingRun->status,
                        'name'   => $existingRun->name,
                    ] : null,
                ],
                'totals'  => $totals,
                'warnings' => $warnings,
                'data'    => $results, // full per-employee breakdowns from the simulator
            ]);
        } catch (\Throwable $th) {
            //throw $th;
            return response()->json([
                'success' => false,
                'message' => 'Failed to run salary preview simulation.',
                'error'   => app()->hasDebugModeEnabled() && config('app.debug') ? $th->getMessage() : null,
            ], 500);
        }
    }
}
