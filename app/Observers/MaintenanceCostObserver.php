<?php

namespace App\Observers;

use App\Models\MaintenanceCost;
use App\Services\Financial\MaintenanceFinancialSyncService;


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
        } catch (\Exception $e) {
            // Silent fail
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
        } catch (\Exception $e) {
            // Silent fail
        }
    }
}
