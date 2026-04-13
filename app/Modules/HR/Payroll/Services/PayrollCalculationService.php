<?php

namespace App\Modules\HR\Payroll\Services;

use App\Models\AdvanceWage;
use App\Models\Employee;
use App\Models\PayrollRun;
use App\Models\Payroll;
use App\Modules\HR\Payroll\Repositories\SalaryTransactionRepository;
use Carbon\Carbon;

class PayrollCalculationService
{
    public function __construct(
        protected SalaryTransactionRepository $transactionRepo,
    ) {}

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
                    reference: null,
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
                    payrollId: $payload['payrollId'] ?? $payroll->id,
                    status: null,
                    extra: $payload['extra']
                );
            }
        }

        // تسوية الأجور المقدمة التي تم بناء حركاتها
        $this->settleAdvanceWages($transactions, $payroll->id);
    }

    /**
     * تسوية الأجور المقدمة بعد حفظ الـ Payroll.
     */
    protected function settleAdvanceWages(array $transactions, int $payrollId): void
    {
        $advanceWageIds = collect($transactions)
            ->filter(fn($txn) => ($txn['reference_type'] ?? null) === AdvanceWage::class)
            ->pluck('reference_id')
            ->filter()
            ->unique()
            ->values()
            ->all();

        if (empty($advanceWageIds)) {
            return;
        }

        AdvanceWage::whereIn('id', $advanceWageIds)
            ->where('status', AdvanceWage::STATUS_PENDING)
            ->update([
                'status'             => AdvanceWage::STATUS_SETTLED,
                'settled_payroll_id' => $payrollId,
                'settled_at'         => now(),
            ]);
    }
}
