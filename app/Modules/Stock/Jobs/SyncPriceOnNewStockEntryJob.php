<?php

declare(strict_types=1);

namespace App\Modules\Stock\Jobs;

use App\Models\InventoryTransaction;
use App\Models\Store;
use App\Modules\Stock\Actions\SyncPriceOnNewStockEntryAction;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

final class SyncPriceOnNewStockEntryJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    // نمرر الـ IDs فقط كقيم نصية/رقمية بسيطة تماشياً مع معيار الـ Tenants
    public function __construct(
        private readonly int $transactionId,
        private readonly int $storeId,
        private readonly ?int $tenantId = null
    ) {
        // $this->onConnection('database');
    }

    public function handle(SyncPriceOnNewStockEntryAction $action): void
    {
        Log::info('SyncProductCurrentBatchPriceJob Working with Tenant ID: ' . $this->tenantId);
        // تفعيل اتصال قاعدة بيانات الـ Tenant أولاً وقبل أي استعلام
        if ($this->tenantId) {
            $tenant = \Spatie\Multitenancy\Models\Tenant::find($this->tenantId);
            if ($tenant) {
                $tenant->makeCurrent();
            }
        }

        // جلب الموديلات فريش من قاعدة بيانات الـ Tenant الصحيحة
        $transaction = InventoryTransaction::findOrFail($this->transactionId);
        $store       = Store::findOrFail($this->storeId);

        // تنفيذ الإجراء
        $action->execute($transaction, $store);
    }
}