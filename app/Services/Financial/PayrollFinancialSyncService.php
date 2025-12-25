<?php

namespace App\Services\Financial;

use App\Enums\FinancialCategoryCode;
use App\Enums\HR\Payroll\SalaryTransactionType;
use App\Models\Branch;
use App\Models\Deduction;
use App\Models\Payroll;
use App\Models\PayrollRun;
use App\Models\SalaryTransaction;
use App\Models\FinancialCategory;
use App\Models\FinancialTransaction;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;


/**
 * Service for syncing payroll data with financial transactions.
 * 
 * This service creates financial transactions from approved/paid payroll runs,
 * enabling financial reporting and analysis of HR expenses.
 */
class PayrollFinancialSyncService
{
    /**
     * Sync a specific payroll run to financial transactions.
     *
     * @param int $payrollRunId
     * @return array Summary of the sync operation
     */
    public function syncPayrollRun(int $payrollRunId): array
    {
        $payrollRun = PayrollRun::with(['payrolls.employee', 'branch'])->find($payrollRunId);

        if (!$payrollRun) {
            return [
                'success' => false,
                'message' => "PayrollRun with ID {$payrollRunId} not found.",
                'synced' => 0,
                'skipped' => 0,
                'errors' => 0,
            ];
        }

        // Only sync approved or completed payroll runs
        if (!in_array($payrollRun->status, [PayrollRun::STATUS_APPROVED, PayrollRun::STATUS_COMPLETED])) {
            return [
                'success' => false,
                'message' => 'PayrollRun must be approved or completed to sync with financial system.',
                'synced' => 0,
                'skipped' => 0,
                'errors' => 0,
            ];
        }

        // Get the required financial categories
        $salaryCategory = FinancialCategory::findByCode(FinancialCategoryCode::PAYROLL_SALARIES);

        if (!$salaryCategory) {
            return [
                'success' => false,
                'message' => 'Payroll financial categories not found. Please run PayrollHRFinancialCategorySeeder first.',
                'synced' => 0,
                'skipped' => 0,
                'errors' => 0,
            ];
        }

        // Check if already synced
        $existingTransaction = FinancialTransaction::where('reference_type', PayrollRun::class)
            ->where('reference_id', $payrollRunId)
            ->exists();

        if ($existingTransaction) {
            return [
                'success' => true,
                'status' => 'skipped',
                'message' => 'PayrollRun already synced to financial system.',
                'synced' => 0,
                'skipped' => 1,
                'errors' => 0,
            ];
        }

        try {
            DB::transaction(function () use ($payrollRun, $salaryCategory) {
                // Calculate totals from approved payrolls
                $payrolls = $payrollRun->payrolls()
                    ->whereIn('status', [Payroll::STATUS_APPROVED, Payroll::STATUS_PAID])
                    ->get();



                $totalNetSalary = $payrolls->sum(function ($payroll) {
                    return $payroll->net_salary;
                });



                if ($totalNetSalary > 0) {
                    // Create main salary expense transaction
                    $transaction = FinancialTransaction::create([
                        'branch_id' => $payrollRun->branch_id,
                        'category_id' => $salaryCategory->id,
                        'amount' => $totalNetSalary,
                        'type' => FinancialTransaction::TYPE_EXPENSE,
                        'transaction_date' => $payrollRun->pay_date ?? now(),
                        'status' => FinancialTransaction::STATUS_PAID,
                        'description' => "Payroll Run: {$payrollRun->name} - {$payrollRun->year}/{$payrollRun->month}",
                        'reference_type' => PayrollRun::class,
                        'reference_id' => $payrollRun->id,
                        'created_by' => auth()->id() ?? $payrollRun->created_by ?? 1,
                        'month' => $payrollRun->month,
                        'year' => $payrollRun->year,
                    ]);
                }

                // === NEW: Create financial transactions for linked deductions ===
                $this->syncLinkedDeductions($payrollRun, $payrolls);
            });

            return [
                'success' => true,
                'status' => 'synced',
                'message' => 'PayrollRun synced to financial system successfully.',
                'payroll_run_id' => $payrollRunId,
                'payroll_run_name' => $payrollRun->name,
                'synced' => 1,
                'skipped' => 0,
                'errors' => 0,
            ];
        } catch (\Exception $e) {


            return [
                'success' => false,
                'status' => 'error',
                'message' => $e->getMessage(),
                'synced' => 0,
                'skipped' => 0,
                'errors' => 1,
            ];
        }
    }

    /**
     * Sync payroll runs for a specific branch within a date range.
     *
     * @param int $branchId
     * @param array $options (month, year, start_date, end_date)
     * @return array
     */
    public function syncPayrollsForBranch(int $branchId, array $options = []): array
    {
        $branch = Branch::find($branchId);

        if (!$branch) {
            return [
                'success' => false,
                'message' => "Branch with ID {$branchId} not found.",
                'synced' => 0,
                'skipped' => 0,
                'errors' => 0,
            ];
        }

        $query = PayrollRun::where('branch_id', $branchId)
            ->whereIn('status', [PayrollRun::STATUS_APPROVED, PayrollRun::STATUS_COMPLETED]);

        // Apply optional filters
        if (isset($options['month']) && isset($options['year'])) {
            $query->where('month', $options['month'])
                ->where('year', $options['year']);
        }

        if (isset($options['year']) && !isset($options['month'])) {
            $query->where('year', $options['year']);
        }

        $payrollRuns = $query->get();

        $synced = 0;
        $skipped = 0;
        $errors = 0;
        $errorDetails = [];

        foreach ($payrollRuns as $payrollRun) {
            $result = $this->syncPayrollRun($payrollRun->id);

            if ($result['success'] && ($result['status'] ?? '') === 'synced') {
                $synced++;
            } elseif ($result['success'] && ($result['status'] ?? '') === 'skipped') {
                $skipped++;
            } else {
                $errors++;
                $errorDetails[] = [
                    'payroll_run_id' => $payrollRun->id,
                    'error' => $result['message'],
                ];
            }
        }

        return [
            'success' => true,
            'message' => "Sync completed for branch: {$branch->name}",
            'branch_id' => $branchId,
            'branch_name' => $branch->name,
            'total_runs' => $payrollRuns->count(),
            'synced' => $synced,
            'skipped' => $skipped,
            'errors' => $errors,
            'error_details' => $errorDetails,
        ];
    }

    /**
     * Sync all approved/completed payroll runs.
     *
     * @param array $options
     * @return array
     */
    public function syncAllBranches(array $options = []): array
    {
        $branches = Branch::where('active', 1)->get();

        $results = [];
        $totalSynced = 0;
        $totalSkipped = 0;
        $totalErrors = 0;

        foreach ($branches as $branch) {
            $result = $this->syncPayrollsForBranch($branch->id, $options);
            $results[] = $result;

            if ($result['success']) {
                $totalSynced += $result['synced'];
                $totalSkipped += $result['skipped'];
                $totalErrors += $result['errors'];
            }
        }

        return [
            'success' => true,
            'message' => 'Payroll sync completed for all branches',
            'total_branches' => $branches->count(),
            'total_synced' => $totalSynced,
            'total_skipped' => $totalSkipped,
            'total_errors' => $totalErrors,
            'branch_results' => $results,
        ];
    }

    /**
     * Delete financial transactions for a payroll run (for re-sync).
     *
     * @param int $payrollRunId
     * @return array
     */
    public function deletePayrollRunTransactions(int $payrollRunId): array
    {
        $deleted = FinancialTransaction::where('reference_type', PayrollRun::class)
            ->where('reference_id', $payrollRunId)
            ->delete();

        return [
            'success' => true,
            'message' => "Deleted {$deleted} financial transactions for PayrollRun #{$payrollRunId}",
            'deleted' => $deleted,
        ];
    }

    /**
     * Get sync status for a payroll run.
     *
     * @param int $payrollRunId
     * @return array
     */
    public function getSyncStatus(int $payrollRunId): array
    {
        $isSynced = FinancialTransaction::where('reference_type', PayrollRun::class)
            ->where('reference_id', $payrollRunId)
            ->exists();

        $transactions = FinancialTransaction::where('reference_type', PayrollRun::class)
            ->where('reference_id', $payrollRunId)
            ->get();

        return [
            'payroll_run_id' => $payrollRunId,
            'is_synced' => $isSynced,
            'transaction_count' => $transactions->count(),
            'total_amount' => $transactions->sum('amount'),
            'transactions' => $transactions->toArray(),
        ];
    }

    /**
     * Sync deductions that are linked to financial categories.
     * Creates separate financial transactions for each linked deduction type.
     *
     * @param PayrollRun $payrollRun
     * @param Collection $payrolls
     * @return void
     */
    protected function syncLinkedDeductions(PayrollRun $payrollRun, Collection $payrolls): void
    {
        // Get all salary transactions for deductions in this payroll run
        $payrollIds = $payrolls->pluck('id')->toArray();

        // Get deductions that have a financial_category_id
        $linkedDeductions = Deduction::whereNotNull('financial_category_id')
            ->with('financialCategory')
            ->get();

        if ($linkedDeductions->isEmpty()) {
            return;
        }

        foreach ($linkedDeductions as $deduction) {
            // Get all salary transactions for this deduction in this payroll run
            $deductionTransactions = SalaryTransaction::whereIn('payroll_id', $payrollIds)
                ->where('reference_type', Deduction::class)
                ->where('reference_id', $deduction->id)
                ->where('type', SalaryTransactionType::TYPE_DEDUCTION->value)
                ->get();

            $totalAmount = $deductionTransactions->sum('amount');

            if ($totalAmount > 0 && $deduction->financialCategory) {
                // Create financial transaction for this deduction
                FinancialTransaction::create([
                    'branch_id' => $payrollRun->branch_id,
                    'category_id' => $deduction->financialCategory->id,
                    'amount' => $totalAmount,
                    'type' => FinancialTransaction::TYPE_EXPENSE,
                    'transaction_date' => $payrollRun->pay_date ?? now(),
                    'status' => FinancialTransaction::STATUS_PAID,
                    'description' => "{$deduction->name} - Payroll: {$payrollRun->name} ({$payrollRun->year}/{$payrollRun->month})",
                    'reference_type' => PayrollRun::class,
                    'reference_id' => $payrollRun->id,
                    'created_by' => auth()->id() ?? $payrollRun->created_by ?? 1,
                    'month' => $payrollRun->month,
                    'year' => $payrollRun->year,
                ]);
            }
        }
    }
}
