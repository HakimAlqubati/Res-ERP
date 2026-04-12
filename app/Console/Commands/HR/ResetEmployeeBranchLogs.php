<?php

namespace App\Console\Commands\HR;

use App\Models\Employee;
use App\Models\EmployeeBranchLog;
use Illuminate\Console\Command;
use Spatie\Multitenancy\Models\Tenant;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ResetEmployeeBranchLogs extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'hr:reset-branch-logs {--tenant= : Optional tenant ID to process}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clear all employee branch logs and create a default one based on current branch and join date';

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
        $this->info("Done resetting employee branch logs.");
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
        if (!\Illuminate\Support\Facades\Schema::hasTable('hr_employees')) {
            $this->line("Table 'hr_employees' does not exist in this context. Skipping.");
            return;
        }

        if (!\Illuminate\Support\Facades\Schema::hasTable('hr_employee_branch_logs')) {
            $this->line("Table 'hr_employee_branch_logs' does not exist in this context. Skipping.");
            return;
        }

        try {
            // 1. Get all employees who have a branch assigned
            $employees = Employee::whereNotNull('branch_id')->get();
            
            if ($employees->isEmpty()) {
                $this->line("No employees found.");
                return;
            }

            // 2. Clear all existing branch logs
            EmployeeBranchLog::query()->delete();

            $count = 0;
            foreach ($employees as $employee) {
                // Determine start date: join_date or fallback to 10 years ago or now
                // Using join_date if available, otherwise now.
                $startAt = $employee->join_date ? Carbon::parse($employee->join_date) : Carbon::now();

                // Create default branch log
                EmployeeBranchLog::create([
                    'employee_id' => $employee->id,
                    'branch_id'   => $employee->branch_id,
                    'start_at'    => $startAt,
                    'end_at'      => null,
                    // 'created_by'  => 1, // Default system user
                ]);
                $count++;
            }

            $this->info("   -> Successfully reset logs for {$count} employees.");
        } catch (\Exception $e) {
            $this->error("   -> Error in current context: " . $e->getMessage());
        }
    }
}
