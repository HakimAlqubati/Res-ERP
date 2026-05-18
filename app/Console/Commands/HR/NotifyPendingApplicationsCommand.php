<?php

namespace App\Console\Commands\HR;

use App\Models\AppLog;
use App\Models\Branch;
use App\Modules\HR\EmployeeApplications\Notifications\BranchManagerNotifier;
use Illuminate\Console\Command;

class NotifyPendingApplicationsCommand extends Command
{
    protected $signature = 'hr:notify-pending-applications';

    protected $description = 'Notify branch managers about pending employee applications (runs every 6 hours).';

    public function handle(BranchManagerNotifier $notifier): int
    {
        // If already in a tenant context (e.g. called manually), process it directly.
        if (\Spatie\Multitenancy\Models\Tenant::current()) {
            return $this->processForTenant(
                \Spatie\Multitenancy\Models\Tenant::current(),
                $notifier
            );
        }

        $tenants = \App\Models\CustomTenantModel::where('active', 1)->get();

        if ($tenants->isEmpty()) {
            $this->warn('No active tenants found.');
            return self::SUCCESS;
        }

        $this->info("Checking pending applications across {$tenants->count()} tenant(s).");

        foreach ($tenants as $tenant) {
            $this->line("──────────────────────────────────────────");
            $this->info("Tenant: {$tenant->name}");

            try {
                $tenant->makeCurrent();
                $this->processForTenant($tenant, $notifier);
            } catch (\Exception $e) {
                $this->error("Error on tenant [{$tenant->name}]: {$e->getMessage()}");

                AppLog::write(
                    message: "Error notifying pending applications for tenant [{$tenant->name}]: {$e->getMessage()}",
                    level: AppLog::LEVEL_ERROR,
                    context: 'HR_PENDING_NOTIFY',
                    extra: ['tenant' => $tenant->name, 'trace' => $e->getTraceAsString()]
                );
            } finally {
                \Spatie\Multitenancy\Models\Tenant::forgetCurrent();
            }
        }

        $this->info('Done.');
        return self::SUCCESS;
    }

    // -------------------------------------------------------------------------

    private function processForTenant($tenant, BranchManagerNotifier $notifier): int
    {
        $branches = Branch::withoutGlobalScopes()
            ->active()
            ->normal()
            ->whereNotNull('manager_id')
            ->with('user')
            ->get();

        if ($branches->isEmpty()) {
            $this->warn("No active branches with a manager in tenant [{$tenant->name}].");
            return self::SUCCESS;
        }

        $notified = 0;

        foreach ($branches as $branch) {
            $count = $notifier->notifyIfPending($branch);
            $notified++;

            $label = $count > 0 ? "({$count} pending)" : "(no pending)";
            $this->line("  ✔ Checked: {$branch->name} {$label}");
        }

        AppLog::write(
            message: "Pending-applications notification sweep completed for tenant [{$tenant->name}]. Branches checked: {$notified}.",
            level: AppLog::LEVEL_INFO,
            context: 'HR_PENDING_NOTIFY',
            extra: ['tenant' => $tenant->name, 'branches_checked' => $notified]
        );

        return self::SUCCESS;
    }
}
