<?php

namespace App\Console\Commands;

use App\Services\GrnPriceSyncService;
use Illuminate\Console\Command;
use Spatie\Multitenancy\Models\Tenant;

class SyncGrnPricesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'grn:sync-prices {--tenant= : Optional tenant ID to run the command under}';



    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle(GrnPriceSyncService $syncService)
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
        
        $this->info('⏳ Starting GRN price sync...');
        $syncService->syncAllGrnPrices();
        $this->info('✅ GRN prices synced successfully.');
    }
}
