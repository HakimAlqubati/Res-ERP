<?php

namespace App\Console\Commands\HR;

use App\Models\AppLog;
use App\Models\EmployeeServiceTermination;
use App\Modules\HR\Employee\Services\EmployeeLifecycleService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Validation\ValidationException;

class AutoApproveTerminationsCommand extends Command
{
    protected $signature = 'hr:auto-approve-terminations
                            {--date= : Process terminations up to this date Y-m-d (default: today)}';

    protected $description = 'Auto-approve scheduled termination requests whose termination_date has arrived.';

    public function handle(EmployeeLifecycleService $service): int
    {
        $date = $this->option('date')
            ? Carbon::parse($this->option('date'))->startOfDay()
            : today();

        $this->info("Processing auto-approvals for date: {$date->toDateString()}");

        // If already inside a tenant context (e.g. called via tenants:artisan), process directly.
        if (\Spatie\Multitenancy\Models\Tenant::current()) {
            return $this->processForTenant(
                \Spatie\Multitenancy\Models\Tenant::current(),
                $service,
                $date
            );
        }

        $tenants = \App\Models\CustomTenantModel::where('active', 1)->get();

        if ($tenants->isEmpty()) {
            $this->warn('No active tenants found.');
            return self::SUCCESS;
        }

        $this->info("Processing {$tenants->count()} tenant(s).");

        foreach ($tenants as $tenant) {
            $this->line('──────────────────────────────────────────');
            $this->info("Tenant: {$tenant->name}");

            try {
                $tenant->makeCurrent();
                $this->processForTenant($tenant, $service, $date);
            } catch (\Exception $e) {
                $this->error("Error on tenant [{$tenant->name}]: {$e->getMessage()}");

                AppLog::write(
                    message: "Auto-approve terminations failed for tenant [{$tenant->name}]: {$e->getMessage()}",
                    level: AppLog::LEVEL_ERROR,
                    context: 'HR_AUTO_APPROVE_TERMINATIONS',
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

    private function processForTenant($tenant, EmployeeLifecycleService $service, Carbon $date): int
    {
        $terminations = EmployeeServiceTermination::withoutGlobalScopes()
            ->where('auto_approve', true)
            ->where('status', EmployeeServiceTermination::STATUS_PENDING)
            ->whereDate('termination_date', '<=', $date)
            ->get();

        if ($terminations->isEmpty()) {
            $this->line("  No scheduled terminations due on or before {$date->toDateString()}.");
            return self::SUCCESS;
        }

        $approved = 0;
        $failed   = 0;

        foreach ($terminations as $termination) {
            $employeeName = $termination->employee?->name ?? "ID #{$termination->employee_id}";

            try {
                $service->approveTermination(
                    $termination,
                    $termination->scheduled_approver_id  // set by the user who enabled auto_approve
                );

                $approved++;
                $this->line("  ✔ Approved: {$employeeName} (termination #{$termination->id})");

                AppLog::write(
                    message: "Auto-approved termination for employee {$employeeName} (#{$termination->employee_id})",
                    level: AppLog::LEVEL_INFO,
                    context: 'HR_AUTO_APPROVE_TERMINATIONS',
                    extra: [
                        'termination_id'       => $termination->id,
                        'employee_id'          => $termination->employee_id,
                        'termination_date'     => $termination->termination_date?->toDateString(),
                        'scheduled_approver_id' => $termination->scheduled_approver_id,
                        'tenant'               => $tenant->name ?? null,
                    ]
                );
            } catch (ValidationException $e) {
                $failed++;
                $messages = collect($e->errors())->flatten()->implode(' | ');
                $this->warn("  ✘ Skipped: {$employeeName} — {$messages}");

                AppLog::write(
                    message: "Auto-approve skipped for employee {$employeeName} (#{$termination->employee_id}): {$messages}",
                    level: AppLog::LEVEL_WARNING,
                    context: 'HR_AUTO_APPROVE_TERMINATIONS',
                    extra: [
                        'termination_id' => $termination->id,
                        'employee_id'    => $termination->employee_id,
                        'errors'         => $e->errors(),
                        'tenant'         => $tenant->name ?? null,
                    ]
                );
            } catch (\Exception $e) {
                $failed++;
                $this->error("  ✘ Error: {$employeeName} — {$e->getMessage()}");

                AppLog::write(
                    message: "Auto-approve error for employee {$employeeName} (#{$termination->employee_id}): {$e->getMessage()}",
                    level: AppLog::LEVEL_ERROR,
                    context: 'HR_AUTO_APPROVE_TERMINATIONS',
                    extra: [
                        'termination_id' => $termination->id,
                        'employee_id'    => $termination->employee_id,
                        'trace'          => $e->getTraceAsString(),
                        'tenant'         => $tenant->name ?? null,
                    ]
                );
            }
        }

        $this->line("  Summary → Approved: {$approved} | Failed/Skipped: {$failed}");

        AppLog::write(
            message: "Auto-approve terminations sweep completed for tenant [{$tenant->name}]. Approved: {$approved}, Failed/Skipped: {$failed}.",
            level: AppLog::LEVEL_INFO,
            context: 'HR_AUTO_APPROVE_TERMINATIONS',
            extra: ['tenant' => $tenant->name, 'approved' => $approved, 'failed' => $failed, 'date' => $date->toDateString()]
        );

        return self::SUCCESS;
    }
}
