<?php

namespace App\Modules\HR\Payroll\Services;

use App\Models\Employee;
use App\Models\Payroll;
use App\Models\PayrollRun;
use App\Modules\HR\Payroll\Repositories\PayrollRepository;
use App\Modules\HR\Payroll\Repositories\SalaryTransactionRepository;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class PayrollCalculationService
{
    public function __construct(
        protected PayrollRepository $payrollRepo,
        protected SalaryTransactionRepository $transactionRepo,
        protected PayrollSimulationService $payrollSimulator
    ) {}



    // إمّا: احذفها تمامًا 
    // أو: خلِّها لا تعتمد على runService

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

        // رجِّع نفس الـ shape الذي يتوقعه PayrollRunService
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
            'penalties'        => $simulation['penalties']       ?? [],
            'penalty_total'   => $simulation['penalty_total'] ?? 0,
            'period_start'     => $simulation['data']['period_start']      ?? null,
            'period_end'       => $simulation['data']['period_end']        ?? null,
            'daily_rate_method' => $simulation['daily_rate_method'] ?? '',
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
    public function generateSalaryTransactions(
        PayrollRun $run,
        Employee $employee,
        array $result,
        Carbon $periodEnd,
        Payroll $payroll
    ): void {
        $transactions = $result['transactions'] ?? [];

        foreach ($transactions as $txn) {
            $amount = (float)($txn['amount'] ?? 0);
            if ($amount <= 0) {
                continue;
            }

            $extra = [
                'payroll_run_id' => $run->id,
                'year'           => $run->year,
                'month'          => $run->month,

                'unit'           => $txn['unit']        ?? null,
                'qty'            => $txn['qty']         ?? null,
                'rate'           => $txn['rate']        ?? null,
                'multiplier'     => $txn['multiplier']  ?? null,

                // Notes and effective percentage for bracket deductions
                'notes'                => $txn['notes']                ?? null,
                'effective_percentage' => $txn['effective_percentage'] ?? null,

                // ربط الـ reference من transaction (مثل ربط القسط بـ SalaryTransaction)
                'reference_type' => $txn['reference_type'] ?? null,
                'reference_id'   => $txn['reference_id']   ?? null,
            ];

            $payload = [
                'employeeId' => $employee->id,
                'amount'     => $amount,
                'date'       => $periodEnd->toDateString(),
                'description' => $txn['description'] ?? null,
                'type'       => $txn['type'] ?? 'other',
                'subType'    => $txn['sub_type'] ?? null,
                'operation' => array_key_exists('operation', $txn) ? $txn['operation'] : null,
                'payrollId'  => $payroll->id,
                // Make sure repo stores this on the model -> payroll_run_id
                'extra'      =>  $extra,
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
}
