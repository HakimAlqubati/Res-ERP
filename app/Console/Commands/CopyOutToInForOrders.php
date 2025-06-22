<?php

namespace App\Console\Commands;

use App\Services\CopyOrderOutToBranchStoreService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Spatie\Multitenancy\Models\Tenant;

class CopyOutToInForOrders extends Command
{
    // protected $signature = 'orders:copy-out-to-in';
    protected $signature = 'orders:copy-out-to-in {--tenant= : Optional tenant ID to run under}';

    protected $description = 'Copy OUT inventory transactions from orders into IN transactions for branch store if available.';

    public function handle()
    {
        $tenantId = $this->option('tenant');

        if ($tenantId) {
            $tenant = Tenant::find($tenantId);

            if (! $tenant) {
                $this->error("❌ Tenant with ID {$tenantId} not found.");
                return;
            }

            $tenant->makeCurrent();
            $this->info("🏢 Tenant [{$tenant->id}] activated.");
        } else {
            $this->info("🌐 Running without tenant context.");
        }
        $this->info('🚀 Starting to copy OUT transactions to IN for orders...');

        try {
            app(CopyOrderOutToBranchStoreService::class)->handle();
            $this->info('✅ Done copying inventory transactions.');
        } catch (\Throwable $e) {
            $this->error('❌ Error: ' . $e->getMessage());
        }
    }
}
