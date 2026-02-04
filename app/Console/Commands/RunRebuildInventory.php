<?php

namespace App\Console\Commands;

use App\Jobs\RebuildInventoryFromSources;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Spatie\Multitenancy\Models\Tenant;

class RunRebuildInventory extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    // protected $signature = 'inventory:rebuild-from-sources {--tenant= : Tenant ID to run the job under}';
    // protected $signature = 'inventory:rebuild-from-sources {--products=* : List of product IDs to rebuild}';
    protected $signature = 'inventory:rebuild-from-sources {--tenant= : Tenant ID (optional) to run the job under}';

    protected $description = 'Dispatch job to rebuild inventory from invoices, GRNs, and supply orders.';

    /**
     * Execute the console command.
     */
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
            Log::info("🔁 RebuildInventory: Running under tenant {$tenant->id}");
        } else {
            $this->info("🌐 Running rebuild job without tenant context.");
            Log::info("🔁 RebuildInventory: Running without tenant");
        }

        if (!$this->confirm('⚠️  This will dispatch a background job to DELETE and REBUILD all transactions. Continue?', true)) {
            return;
        }

        Log::info('✅ start run of rebuild.');
        $this->info('📦 Dispatching job to rebuild inventory...');
        dispatch(new RebuildInventoryFromSources());

        $this->info('✅ Job dispatched successfully! The rebuild will happen in the background.');
        $this->info('👉 Check your queue worker to see progress.');
    }
}
