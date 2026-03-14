<?php

namespace App\Console\Commands\HR;

use App\Modules\HR\Overtime\OvertimeService;
use Illuminate\Console\Command;
use Carbon\Carbon;

class AutoProcessOvertimeCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'hr:overtime:auto-process {date?} {--branch_id=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Automatically process suggested overtime for employees based on attendance records.';

    /**
     * Execute the console command.
     *
     * @param OvertimeService $overtimeService
     * @return int
     */
    public function handle(OvertimeService $overtimeService)
    {
        $date = $this->argument('date') ?: Carbon::yesterday()->toDateString();
        $branchId = $this->option('branch_id');

        // Check if we are already in a tenant context
        if (\Spatie\Multitenancy\Models\Tenant::current()) {
            return $this->processForTenant(\Spatie\Multitenancy\Models\Tenant::current(), $date, $branchId, $overtimeService);
        }

        // If not in a tenant context, loop through all active tenants
        $tenants = \App\Models\CustomTenantModel::where('active', 1)->get();
        
        if ($tenants->isEmpty()) {
            $this->warn("No active tenants found.");
            return 0;
        }

        $this->info("Starting automatic overtime processing for " . $tenants->count() . " tenants.");

        foreach ($tenants as $tenant) {
            $this->line("--------------------------------------------------");
            $this->info("Processing Tenant: {$tenant->name}");
            
            try {
                $tenant->makeCurrent();
                $this->processForTenant($tenant, $date, $branchId, $overtimeService);
            } catch (\Exception $e) {
                $this->error("Error processing tenant {$tenant->name}: " . $e->getMessage());
            } finally {
                \Spatie\Multitenancy\Models\Tenant::forgetCurrent();
            }
        }

        $this->info("All tenants processed.");
        return 0;
    }

    /**
     * Process overtime for a specific tenant.
     */
    protected function processForTenant($tenant, $date, $branchId, $overtimeService)
    {
        $this->info("Starting automatic overtime processing for date: {$date}" . ($branchId ? " (Branch ID: {$branchId})" : ""));

        $results = $overtimeService->autoProcessSuggestedOvertime($date, $branchId);

        if (empty($results)) {
            $this->warn("No processing results found or no active branches for tenant: {$tenant->name}");
            return 0;
        }

        $headers = ['Branch', 'Processed Records / Status'];
        $data = [];
        foreach ($results as $branchName => $status) {
            $data[] = [$branchName, $status];
        }

        $this->table($headers, $data);
        return 0;
    }
}
