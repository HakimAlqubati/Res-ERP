<?php

namespace App\Console\Commands;

use App\Jobs\ManufacturingBackfillJob;
use App\Services\ManufacturingBackfillService;

use Illuminate\Console\Command;
use Spatie\Multitenancy\Models\Tenant;

class RunManufacturingBackfillJob extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'run:manufacturing-backfill-service {storeId} {tenantId?}'; // tenantId is optional




    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle(ManufacturingBackfillService $manufacturingBackfillService)

    {
        $storeId = $this->argument('storeId'); // الحصول على storeId من المعاملات المرسلة
        $tenantId = $this->argument('tenantId'); // Get the optional tenantId argument

        $this->info("Running backfill for Store ID: {$storeId}");
        if ($tenantId) {
            $tenant = Tenant::find($tenantId);

            if (! $tenant) {
                $this->error("❌ Tenant with ID {$tenantId} not found.");
                return;
            }

            $tenant->makeCurrent();
            $this->info("🏢 Tenant [{$tenant->id}] activated.");
        } else {
            $this->info("No Tenant ID provided, proceeding without tenant.");
        }
        $this->info("Running backfill for Store ID: {$storeId}");

        try {
            $manufacturingBackfillService->handleFromSimulation($storeId);
            $this->info('Manufacturing backfill process completed successfully.');
        } catch (\Exception $e) {
            $this->error('An error occurred: ' . $e->getMessage());
        }
    }
}