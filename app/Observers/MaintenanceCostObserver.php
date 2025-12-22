<?php

namespace App\Observers;

use App\Models\MaintenanceCost;
use App\Services\Financial\MaintenanceFinancialSyncService;
use Illuminate\Support\Facades\Log;

/**
 * Observer لنموذج MaintenanceCost
 * 
 * يقوم بمزامنة تكاليف الصيانة مع النظام المالي تلقائياً عند الإنشاء
 */
class MaintenanceCostObserver
{
    public function __construct(
        protected MaintenanceFinancialSyncService $syncService
    ) {}

    /**
     * Handle the MaintenanceCost "created" event.
     * 
     * عند إنشاء تكلفة جديدة، يتم مزامنتها مع النظام المالي
     */
    public function created(MaintenanceCost $cost): void
    {
        // تخطي إذا كان المبلغ صفر
        if ($cost->amount <= 0) {
            return;
        }

        try {
            $result = $this->syncService->syncMaintenanceCost($cost->id);

            if ($result['success'] && ($result['status'] ?? '') === 'synced') {
                Log::info('MaintenanceCost auto-synced to financial system', [
                    'maintenance_cost_id' => $cost->id,
                    'amount' => $cost->amount,
                    'cost_type' => $cost->cost_type,
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Failed to auto-sync MaintenanceCost to financial system', [
                'maintenance_cost_id' => $cost->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Handle the MaintenanceCost "deleted" event.
     * 
     * عند حذف تكلفة، يتم حذف المعاملة المالية المرتبطة
     */
    public function deleted(MaintenanceCost $cost): void
    {
        try {
            $this->syncService->deleteFinancialTransaction($cost->id);

            Log::info('MaintenanceCost financial transaction deleted', [
                'maintenance_cost_id' => $cost->id,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to delete MaintenanceCost financial transaction', [
                'maintenance_cost_id' => $cost->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
