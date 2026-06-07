<?php

namespace App\Console\Commands\HR;

use App\Models\EmployeeServiceTermination;
use App\Models\CustomTenantModel;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;

class SyncTerminationBranchCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'hr:sync-termination-branch {--tenant= : Optional tenant ID to process}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync EmployeeServiceTermination branch_id from the related employee for all tenants';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $tenantId = $this->option('tenant');

        if ($tenantId) {
            $tenant = CustomTenantModel::find($tenantId);
            if (!$tenant) {
                $this->error("Tenant ID {$tenantId} not found.");
                return;
            }
            $this->processTenant($tenant);
        } else {
            // 1. Process All Active Tenants
            $this->info("--------------------------------------------------");
            $this->info("Processing All Active Tenants...");
            $tenants = CustomTenantModel::where('active', 1)->get();

            if ($tenants->isEmpty()) {
                $this->warn("No active tenants found.");
            } else {
                foreach ($tenants as $tenant) {
                    $this->processTenant($tenant);
                }
            }
        }

        $this->info("--------------------------------------------------");
        $this->info("Done syncing termination branches.");
    }

    /**
     * Switch to tenant context and sync.
     */
    protected function processTenant($tenant)
    {
        $this->line("Processing Tenant: [{$tenant->id}] {$tenant->name}");

        try {
            $tenant->makeCurrent();
            $this->syncTerminations();
        } catch (\Exception $e) {
            $this->error("Failed to process tenant {$tenant->name}: " . $e->getMessage());
        } finally {
            $tenant->forgetCurrent();
        }
    }

    /**
     * Core logic to sync terminations for the current database context.
     */
    protected function syncTerminations()
    {
        if (!Schema::hasTable('hr_employees') || !Schema::hasTable('hr_employee_service_terminations')) {
            $this->line("Required tables do not exist in this context. Skipping.");
            return;
        }

        try {
            $count = 0;
            // Get all terminations that need branch_id
            $terminations = EmployeeServiceTermination::whereNull('branch_id')
                ->with('employee')
                ->get();

            foreach ($terminations as $termination) {
                if ($termination->employee && $termination->employee->branch_id) {
                    $termination->update([
                        'branch_id' => $termination->employee->branch_id
                    ]);
                    $count++;
                }
            }

            $this->info("   -> Successfully updated branch_id for {$count} termination records.");
        } catch (\Exception $e) {
            $this->error("   -> Error in current context: " . $e->getMessage());
        }
    }
}
