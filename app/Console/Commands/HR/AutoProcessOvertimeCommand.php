<?php

namespace App\Console\Commands\HR;

use App\Models\AppLog;
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

                \App\Models\AppLog::write(
                    message: "Error auto-processing overtime for tenant: {$tenant->name}: " . $e->getMessage(),
                    level: \App\Models\AppLog::LEVEL_ERROR,
                    context: 'HR_OVERTIME_AUTO',
                    extra: [
                        'tenant' => $tenant->name,
                        'date' => $date,
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString()
                    ]
                );
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
        AppLog::write(
            message: "Starting automatic overtime processing for tenant: {$tenant->name}",
            level: AppLog::LEVEL_INFO,
            context: 'HR_OVERTIME_AUTO',
            extra: [
                'tenant' => $tenant->name,
                'date' => $date,
                'branch_id' => $branchId
            ]
        );
        $results = $overtimeService->autoProcessSuggestedOvertime($date, $branchId);

        if (empty($results)) {
            $this->warn("No processing results found or no active branches for tenant: {$tenant->name}");
            return 0;
        }

        $headers = ['Branch', 'Processed Records / Status'];
        $data = [];
        $totalProcessed = 0;

        foreach ($results as $branchName => $status) {
            // Only show branches with activity or errors
            if ($status !== 0 && $status !== "0") {
                $data[] = [$branchName, $status];
                if (is_numeric($status)) {
                    $totalProcessed += $status;
                }
            }
        }

        if (empty($data)) {
            $this->warn("No records were suggested or processed for any branch in tenant: {$tenant->name}");
        } else {
            $this->table($headers, $data);

            // Log the activity
            \App\Models\AppLog::write(
                message: "Auto-processed overtime for tenant: {$tenant->name} on date: {$date}. Total records: {$totalProcessed}",
                level: \App\Models\AppLog::LEVEL_INFO,
                context: 'HR_OVERTIME_AUTO',
                extra: [
                    'tenant' => $tenant->name,
                    'date' => $date,
                    'branch_id' => $branchId,
                    'results' => $results
                ]
            );
        }

        return 0;
    }
}
