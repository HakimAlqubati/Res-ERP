<?php

namespace App\Services\HR\Payroll;

use App\Models\Employee;
use App\Models\Payroll;
use App\Models\PayrollRun;
use App\Repositories\HR\Salary\PayrollRepository;
use App\Repositories\HR\Salary\SalaryTransactionRepository;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class PayrollCalculationService
{
    public function __construct(
        protected PayrollRepository $payrollRepo,
        protected SalaryTransactionRepository $transactionRepo,
        protected PayrollSimulationService $payrollSimulator
    ) {}

    /**
     * Calculate and persist payroll for a single employee within a specific PayrollRun.
     */
    public function calculateForEmployeeInRun(PayrollRun $run, Employee $employee): array
    {
        try {
            return DB::transaction(function () use ($run, $employee) {

                // Prevent duplicates within the same run
                $exists = Payroll::withTrashed()
                    ->where('payroll_run_id', $run->id)
                    ->where('employee_id', $employee->id)
                    ->exists();

                if ($exists) {
                    return [
                        'success' => false,
                        'message' => "Payroll already exists for employee [{$employee->name}] in run #{$run->id}.",
                    ];
                }

                // Simulate first (non-persistent)
                $simulationResults = $this->payrollSimulator->simulateForEmployees(
                    [$employee->id],
                    $run->year,
                    $run->month
                );
                $simulation = $simulationResults[0] ?? null;

                if (!$simulation || !$simulation['success']) {
                    return [
                        'success' => false,
                        'message' => $simulation['message'] ?? 'Payroll simulation failed.',
                    ];
                }

                $result     = $simulation['data'];
                $netSalary  = $result['net_salary'];
                $debtAmount = $result['debt_amount'] ?? 0;

                // Persist Payroll linked to the run (no 'name' field)
                $payroll = $this->payrollRepo->create([
                    'payroll_run_id'         => $run->id,
                    'employee_id'            => $employee->id,
                    'branch_id'              => $run->branch_id, // denormalized for speed
                    'year'                   => $run->year,
                    'month'                  => $run->month,
                    'period_start_date'      => $result['period_start'],
                    'period_end_date'        => $result['period_end'],
                    'base_salary'            => $result['base_salary'],
                    'total_allowances'       => 0,
                    'overtime_amount'        => $result['overtime_amount'],
                    'total_deductions'       => $result['absence_deduction'],
                    'gross_salary'           => $result['gross_salary'],
                    'net_salary'             => $netSalary,
                    'debt_amount'            => $debtAmount,
                    'currency'               => getDefaultCurrency(),
                    'status'                 => Payroll::STATUS_PENDING,
                    'created_by'             => auth()->id() ?? null,
                    'notes'                  => 'Auto payroll via simulator',
                ]);

                // Persist transactions and link to run
                $periodEnd = Carbon::parse($result['period_end']);
                $this->generateSalaryTransactions($run, $employee, $result, $periodEnd, $payroll);

                return [
                    'success' => true,
                    'message' => 'Payroll calculated and saved successfully.',
                    'data'    => $payroll,
                ];
            });
        } catch (\Throwable $e) {
            return [
                'success' => false,
                'message' => "Unexpected error: {$e->getMessage()}",
            ];
        }
    }

    // إمّا: احذفها تمامًا 
    // أو: خلِّها لا تعتمد على runService

    public function calculateForEmployee(Employee $employee, int $year, int $month): array
    {
        // بديل بدون أي استدعاء لـ PayrollRunService
        $simulationResults = $this->payrollSimulator->simulateForEmployees([$employee->id], $year, $month);
        $simulation = $simulationResults[0] ?? null;
 
        if (!$simulation || !$simulation['success']) {
            return [
                'success' => false,
                'message' => $simulation['message'] ?? 'Payroll simulation failed.',
            ];
        }

        // رجِّع نفس الـ shape الذي يتوقعه PayrollRunService
        return [
            'success'          => true,
            'base_salary'      => $simulation['data']['base_salary']      ?? 0,
            'overtime_amount'  => $simulation['data']['overtime_amount']  ?? 0,
            'total_allowances' => $simulation['data']['total_allowances'] ?? 0,
            'total_deductions' => $simulation['total_deduction'] ?? 0,
            'gross_salary'     => $simulation['data']['gross_salary']     ?? 0,
            'net_salary'       => $simulation['data']['net_salary']       ?? 0,
            // لو تحتاج معاملات مفصلة:
            'transactions'     => $simulation['transactions']     ?? [],
            'period_start'     => $simulation['data']['period_start']      ?? null,
            'period_end'       => $simulation['data']['period_end']        ?? null,
        ];
    }


    /**
     * Persist salary transactions for a payroll item.
     *
     * @param PayrollRun $run
     * @param Employee   $employee
     * @param array      $calc       // نتيجة calculateForEmployee (تحتوي 'transactions')
     * @param Carbon     $periodStart
     * @param Payroll    $payroll
     */
    public function generateSalaryTransactions(PayrollRun $run, Employee $employee, array $result, Carbon $periodEnd, Payroll $payroll): void
    {
        $transactions = $result['transactions'] ?? [];

        foreach ($transactions as $txn) {
            $amount = (float)($txn['amount'] ?? 0);
            if ($amount <= 0) {
                continue;
            }

            $payload = [
                'employeeId' => $employee->id,
                'amount'     => $amount,
                'date'       => $periodEnd->toDateString(),
                'description' => $txn['description'] ?? null,
                'type'       => $txn['type'] ?? 'other',
                'subType'    => $txn['sub_type'] ?? null,
                'operation'  => $txn['operation'] ?? '+',
                'payrollId'  => $payroll->id,
                // Make sure repo stores this on the model -> payroll_run_id
                'extra'      => array_merge($txn['extra'] ?? [], [
                    'payroll_run_id' => $run->id,
                    'year'           => $run->year,
                    'month'          => $run->month,
                ]),
            ];

            if ($payload['operation'] === '-') {
                // structured "deduction" API
                $this->transactionRepo->addDeduction(
                    payrollRunId: $run->id,
                    employeeId: $payload['employeeId'],
                    amount: $payload['amount'],
                    date: $payload['date'],
                    description: $payload['description'] ?? 'Deduction',
                    type: $payload['type'],
                    subType: $payload['subType'],
                    payrollId: $payload['payrollId'] ?? $payroll->id,
                    extra: $payload['extra']
                );
            } else {
                $this->transactionRepo->addTransaction(
                    payrollRunId: $run->id,
                    employeeId: $payload['employeeId'],
                    amount: $payload['amount'],
                    date: $payload['date'],
                    type: $payload['type'],
                    operation: $payload['operation'],
                    description: $payload['description'] ?? 'Addition',
                    payrollId: $payload['payrollId'] ?? $payroll->id
                );
            }
        }
    }

    /**
     * Bulk calculate for multiple employees in a given run.
     */
    public function calculateForEmployeesInRun(PayrollRun $run, array $employeeIds): array
    {
        $results = [];
        $employees = Employee::whereIn('id', $employeeIds)->get();

        foreach ($employees as $employee) {
            $result = $this->calculateForEmployeeInRun($run, $employee);
            $results[] = [
                'success'     => $result['success'],
                'message'     => $result['message'],
                'employee_id' => $employee->id,
                'employee_no' => $employee->employee_no,
                'name'        => $employee->name,
            ];
        }

        return $results;
    }

    /**
     * Bulk calculate with auto run (fallback).
     */
    public function calculateForEmployees(array $employeeIds, int $year, int $month): array
    {
        $results = [];
        $employees = Employee::whereIn('id', $employeeIds)->get();

        foreach ($employees as $employee) {
            $results[] = $this->calculateForEmployee($employee, $year, $month);
        }

        // normalize output shape (like before)
        return array_map(function ($row) {
            return [
                'success' => $row['success'] ?? false,
                'message' => $row['message'] ?? '',
                'employee_id' => $row['data']->employee_id ?? null,
                'employee_no' => null,
                'name'        => null,
            ];
        }, $results);
    }
}
