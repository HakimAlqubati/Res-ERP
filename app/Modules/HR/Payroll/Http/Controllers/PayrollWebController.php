<?php

namespace App\Modules\HR\Payroll\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Modules\HR\Payroll\Contracts\PayrollSimulatorInterface;
use App\Modules\HR\Payroll\Contracts\PayrollRunnerInterface;
use App\Modules\HR\Payroll\DTOs\RunPayrollData;
use App\Modules\HR\Payroll\Http\Requests\RunPayrollRequest;
use Carbon\Carbon;
use Illuminate\Http\Request;

class PayrollWebController extends Controller
{
    public function __construct(
        protected PayrollSimulatorInterface $simulationService,
        protected PayrollRunnerInterface $runnerService
    ) {}

    /**
     * Web: Simulate salaries by employee IDs
     * Expects: employee_ids (array), year, month
     */
    public function simulateSalariesByEmployeeIds(Request $request)
    {
        $validated = [
            'employee_ids'   => [23],
            'year'           => 2026,
            'month'          => 1,
        ];
        // $validated = $request->validate([
        //     'employee_ids'   => 'nullable|array|min:1',
        //     'employee_ids.*' => 'integer|exists:hr_employees,id',
        //     'year'           => 'nullable|integer|min:2000',
        //     'month'          => 'nullable|integer|between:1,12',
        // ]);

        $results = $this->simulationService->simulateForEmployees(
            $validated['employee_ids'],
            $validated['year'],
            $validated['month']
        );

        return view('hr.payroll.simulation', [
            'results' => $results,
            'year' => $validated['year'],
            'month' => $validated['month']
        ]);
    }

    /**
     * Web: Preview Payroll for Branch/Month/Year
     * Expects: branch_id, year, month
     */
    public function previewByBranchYearMonth(Request $request)
    {
        // For GET requests, we might want to show the form if params are missing?
        // But the user asked for "equivalent of the API routes", which usually assume data is passed.
        // I will adhere to Validation -> Result. If validation fails, Laravel redirects back.
        // To make it easier for testing, if method is GET and no params, I could show empty form? 
        // No, I'll stick to logic: input provided -> show result. 

        $validated = $request->validate([
            'branch_id'      => 'required|integer|exists:branches,id',
            'year'           => 'required|integer|min:2000|max:2100',
            'month'          => 'required|integer|between:1,12',
        ]);

        $branchId = (int) $validated['branch_id'];
        $year     = (int) $validated['year'];
        $month    = (int) $validated['month'];

        $employeeIds = Employee::query()->active()
            ->where('branch_id', $branchId)
            ->pluck('id')
            ->all();

        $results = $this->simulationService->simulateForEmployees($employeeIds, $year, $month);

        $totals = [
            'total_gross' => 0.0,
            'total_net' => 0.0,
            'total_deductions' => 0.0,
            'count' => count($results)
        ];

        foreach ($results as $row) {
            if ($row['success'] ?? false) {
                $data = $row['data'] ?? [];
                $totals['total_gross'] += (float)($data['gross_salary'] ?? 0);
                $totals['total_net'] += (float)($data['net_salary'] ?? 0);
                $totals['total_deductions'] += (float)($data['absence_deduction'] ?? 0);
            }
        }

        return view('hr.payroll.preview', compact('results', 'totals', 'year', 'month', 'branchId'));
    }

    /**
     * Web: Simulate Run (Runner Logic)
     * Matches RunPayrollController::simulate logic
     */
    public function simulateRun(RunPayrollRequest $request)
    {
        $dto = RunPayrollData::fromArray($request->validatedPayload());
        $result = $this->runnerService->simulate($dto);

        return view('hr.payroll.run_simulation', ['data' => $result]);
    }
}
