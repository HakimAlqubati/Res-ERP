<?php

namespace App\Console\Commands\HR;

use App\Models\EmployeePeriodHistory;
use App\Models\EmployeeBranchLog;
use Illuminate\Console\Command;
use Carbon\Carbon;
use Illuminate\Support\Facades\Schema;

class SyncEmployeePeriodHistoryBranch extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'hr:sync-period-history-branch {--tenant= : Optional tenant ID to process}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync EmployeePeriodHistory branch_id according to EmployeeBranchLog for each employee';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $tenantId = $this->option('tenant');

        if ($tenantId) {
            $tenant = \App\Models\CustomTenantModel::find($tenantId);
            if (!$tenant) {
                $this->error("Tenant ID {$tenantId} not found.");
                return;
            }
            $this->processTenant($tenant);
        } else {
            // 1. Process Landlord (Central Database)
            $this->info("--------------------------------------------------");
            $this->info("Step 1: Processing Landlord (Central Database)...");
            $this->syncLogs();

            // 2. Process All Active Tenants
            $this->info("--------------------------------------------------");
            $this->info("Step 2: Processing All Active Tenants...");
            $tenants = \App\Models\CustomTenantModel::where('active', 1)->get();

            if ($tenants->isEmpty()) {
                $this->warn("No active tenants found.");
            } else {
                foreach ($tenants as $tenant) {
                    $this->processTenant($tenant);
                }
            }
        }

        $this->info("--------------------------------------------------");
        $this->info("Done syncing employee period history branches.");
    }

    /**
     * Switch to tenant context and sync logs.
     */
    protected function processTenant($tenant)
    {
        $this->line("Processing Tenant: [{$tenant->id}] {$tenant->name}");

        try {
            $tenant->makeCurrent();
            $this->syncLogs();
        } catch (\Exception $e) {
            $this->error("Failed to process tenant {$tenant->name}: " . $e->getMessage());
        } finally {
            $tenant->forgetCurrent();
        }
    }

    /**
     * Core logic to sync logs for the current database context.
     */
    protected function syncLogs()
    {
        // Check if the required tables exist in the current connection
        if (!Schema::hasTable('hr_employees') || 
            !Schema::hasTable('hr_employee_period_histories') || 
            !Schema::hasTable('hr_employee_branch_logs')) {
            $this->line("Required tables do not exist in this context. Skipping.");
            return;
        }

        try {
            // Get all branch logs grouped by employee_id to avoid N+1 queries
            $allLogs = EmployeeBranchLog::orderBy('start_at', 'asc')->get()->groupBy('employee_id');

            if ($allLogs->isEmpty()) {
                $this->line("No employee branch logs found.");
                return;
            }

            $updatedCount = 0;
            
            // Process period histories in chunks
            EmployeePeriodHistory::chunk(200, function ($histories) use ($allLogs, &$updatedCount) {
                foreach ($histories as $history) {
                    $logs = $allLogs->get($history->employee_id);
                    if (!$logs) {
                        continue; // No logs for this employee
                    }

                    // We will determine the target date. 
                    // Usually we use start_date of the history. If not exist, we use current date as fallback.
                    $targetDate = $history->start_date ? Carbon::parse($history->start_date) : Carbon::now();
                    $foundBranchId = null;

                    foreach ($logs as $log) {
                        $startAt = Carbon::parse($log->start_at);
                        $endAt = $log->end_at ? Carbon::parse($log->end_at) : null;

                        if ($startAt->lte($targetDate) && (!$endAt || $endAt->gte($targetDate))) {
                            $foundBranchId = $log->branch_id;
                            break;
                        }
                    }

                    // If we found a matching log and the branch_id is different, update it
                    if ($foundBranchId && $history->branch_id !== $foundBranchId) {
                        // Update the history
                        $history->update(['branch_id' => $foundBranchId]);
                        $updatedCount++;
                    }
                }
            });

            $this->info("   -> Successfully updated branch_id for {$updatedCount} period history records.");
        } catch (\Exception $e) {
            $this->error("   -> Error in current context: " . $e->getMessage());
        }
    }
}
