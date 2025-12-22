<?php

namespace App\Observers;

use App\Models\PayrollRun;
use App\Services\Financial\PayrollFinancialSyncService;
use Illuminate\Support\Facades\Log;

/**
 * Observer for PayrollRun model.
 * 
 * Automatically syncs approved/completed payroll runs with the financial system.
 */
class PayrollRunObserver
{
    public function __construct(
        protected PayrollFinancialSyncService $syncService
    ) {}

    /**
     * Handle the PayrollRun "updated" event.
     * 
     * When status changes to approved or completed, sync with financial system.
     */
    public function updated(PayrollRun $payrollRun): void
    {
        // Check if status was changed
        if (!$payrollRun->isDirty('status')) {
            return;
        }

        $newStatus = $payrollRun->status;
        $oldStatus = $payrollRun->getOriginal('status');

        // Only sync when status changes TO approved or completed
        if (in_array($newStatus, [PayrollRun::STATUS_APPROVED, PayrollRun::STATUS_COMPLETED])) {
            // Don't re-sync if already in one of these statuses
            if (in_array($oldStatus, [PayrollRun::STATUS_APPROVED, PayrollRun::STATUS_COMPLETED])) {
                return;
            }

            try {
                $result = $this->syncService->syncPayrollRun($payrollRun->id);

                if ($result['success'] && ($result['status'] ?? '') === 'synced') {
                    Log::info('PayrollRun synced to financial system', [
                        'payroll_run_id' => $payrollRun->id,
                        'status' => $newStatus,
                    ]);
                }
            } catch (\Exception $e) {
                Log::error('Failed to sync PayrollRun to financial system', [
                    'payroll_run_id' => $payrollRun->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    /**
     * Handle the PayrollRun "deleted" event.
     * 
     * Clean up financial transactions when payroll run is deleted.
     */
    public function deleted(PayrollRun $payrollRun): void
    {
        try {
            $this->syncService->deletePayrollRunTransactions($payrollRun->id);

            Log::info('PayrollRun financial transactions deleted', [
                'payroll_run_id' => $payrollRun->id,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to delete PayrollRun financial transactions', [
                'payroll_run_id' => $payrollRun->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
