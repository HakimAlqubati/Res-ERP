<?php

declare(strict_types=1);

namespace App\Modules\Stock\Jobs;

use App\Models\Store;
use App\Modules\Stock\Actions\SyncProductCurrentBatchPriceAction;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Spatie\Multitenancy\Models\Tenant;

final class SyncProductCurrentBatchPriceJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        private readonly int $productId,
        private readonly int $storeId,
        private readonly ?int $tenantId = null
    ) {
        $this->onConnection('database');
    }

    public function handle(): void
    {
        Log::info('SyncProductCurrentBatchPriceJob Working with Tenant ID: '.$this->tenantId);

        if ($this->tenantId) {
            $tenant = Tenant::find($this->tenantId);
            if ($tenant) {
                $tenant->makeCurrent();
            }
        }
        // جلب موديل المخزن من قاعدة بيانات الـ Tenant
        $store = Store::findOrFail($this->storeId);
        $action = app(SyncProductCurrentBatchPriceAction::class);
        $action->execute($this->productId, $store);
    }
}
