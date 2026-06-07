<?php

namespace App\Console\Commands\HR;

use App\Models\CustomTenantModel;
use App\Modules\HR\Leaves\InitEmployeeLeaves\Init;
use Illuminate\Console\Command;
use Spatie\Multitenancy\Models\Tenant;

/**
 * Class InitLeaveBalancesCommand
 * 
 * Artisan command to initialize employee leave balances across tenants.
 * 
 * @package App\Console\Commands\HR
 */
class InitLeaveBalancesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'hr:leaves:init';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Initialize and calculate leave balances for all active employees across all tenants.';

    /**
     * Execute the console command.
     *
     * @param Init $initService The leave initialization coordinator service.
     * @return int
     */
    public function handle(Init $initService): int
    {
        // Check if we are already in a tenant context
        if (Tenant::current()) {
            return $this->processForTenant(Tenant::current(), $initService);
        }

        // If not in a tenant context, loop through all active tenants[cite: 11]
        $tenants = CustomTenantModel::where('active', 1)->get();

        if ($tenants->isEmpty()) {
            $this->warn("No active tenants found.");
            return 0;
        }

        $this->info("Starting leave balances initialization for {$tenants->count()} tenants.");

        foreach ($tenants as $tenant) {
            $this->line("--------------------------------------------------");
            $this->info("Processing Tenant: {$tenant->name}");

            try {
                $tenant->makeCurrent(); // Switch to the tenant's database[cite: 11]
                $this->processForTenant($tenant, $initService);
            } catch (\Exception $e) {
                $this->error("Error processing tenant {$tenant->name}: " . $e->getMessage());
            } finally {
                Tenant::forgetCurrent(); // Always clear the tenant context[cite: 11]
            }
        }

        $this->info("All tenants processed successfully.");
        return 0;
    }

    /**
     * Process the leave initialization for a specific tenant.
     *
     * @param mixed $tenant
     * @param Init  $initService
     * @return int
     */
    protected function processForTenant($tenant, Init $initService): int
    {
        $this->info("Executing leave balance logic for tenant: {$tenant->name}...");



        // ---------------------------------------------------------
        // Core Logic Execution
        // Automatically injects and runs the Init class we built.
        // ---------------------------------------------------------
        $initService->handle();

        $this->info("Successfully initialized leave balances for tenant: {$tenant->name}");


        return 0;
    }
}
