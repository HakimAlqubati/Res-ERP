<?php

namespace App\Modules\HR\Payroll\Contracts;

/**
 * Interface for payroll financial sync services.
 * 
 * Provides a contract for syncing payroll data with the financial system.
 */
interface PayrollFinancialSyncInterface
{
    /**
     * Sync a specific payroll run to financial transactions.
     *
     * @param int $payrollRunId
     * @return array Summary of the sync operation
     */
    public function syncPayrollRun(int $payrollRunId): array;

    /**
     * Sync payroll runs for a specific branch.
     *
     * @param int $branchId
     * @param array $options (month, year, start_date, end_date)
     * @return array
     */
    public function syncPayrollsForBranch(int $branchId, array $options = []): array;

    /**
     * Sync all approved/completed payroll runs for all branches.
     *
     * @param array $options
     * @return array
     */
    public function syncAllBranches(array $options = []): array;

    /**
     * Delete financial transactions for a payroll run.
     *
     * @param int $payrollRunId
     * @return array
     */
    public function deletePayrollRunTransactions(int $payrollRunId): array;

    /**
     * Get sync status for a payroll run.
     *
     * @param int $payrollRunId
     * @return array
     */
    public function getSyncStatus(int $payrollRunId): array;
}
