<?php

declare(strict_types=1);

namespace App\Console\Commands\HR;

use App\Models\AppLog;
use App\Modules\HR\SalaryIncrease\Contracts\EmployeeSalaryLogServiceInterface;
use App\Modules\HR\SalaryIncrease\Support\MultiTenantCommandRunner;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;
use Throwable;

class SyncEffectiveSalariesCommand extends Command
{
    protected $signature = 'hr:sync-effective-salaries
                            {--tenant= : Target a specific tenant by ID, name, domain, or database}
                            {--date= : Reference date Y-m-d (default: today)}';

    protected $description = 'Synchronize hr_employees.salary cache with effective salary logs for the current (or specified) month across tenants.';

    public function handle(
        MultiTenantCommandRunner $runner,
        EmployeeSalaryLogServiceInterface $salaryLogService,
    ): int {
        $asOf = $this->option('date')
            ? Carbon::parse((string) $this->option('date'))->startOfMonth()
            : now()->startOfMonth();

        $this->info("Synchronizing effective salaries as of: {$asOf->toDateString()}");

        return $runner->run(
            tenantFilter: $this->option('tenant') ? (string) $this->option('tenant') : null,
            callback: fn (string $label): int => $this->runForContext($label, $salaryLogService, $asOf),
            onError: function ($tenant, Throwable $e) use ($asOf): void {
                $this->error("Error on tenant [{$tenant->name}]: {$e->getMessage()}");

                AppLog::write(
                    message: "Sync effective salaries failed for tenant [{$tenant->name}]: {$e->getMessage()}",
                    level: AppLog::LEVEL_ERROR,
                    context: 'HR_SYNC_EFFECTIVE_SALARIES',
                    extra: ['tenant' => $tenant->name, 'date' => $asOf->toDateString()]
                );
            },
        );
    }

    private function runForContext(
        string $label,
        EmployeeSalaryLogServiceInterface $salaryLogService,
        Carbon $asOf
    ): int {
        if (! Schema::hasTable('hr_employee_salary_logs')) {
            $this->line("[{$label}] Skipped: hr_employee_salary_logs table not migrated yet.");

            return self::SUCCESS;
        }

        $stats = $salaryLogService->syncCurrentEffectiveSalaries($asOf);

        $this->info(sprintf(
            '[%s] checked=%d, synced=%d, failed=%d',
            $label,
            $stats['checked'],
            $stats['synced'],
            $stats['failed'],
        ));

        foreach ($stats['errors'] as $error) {
            $this->warn("  - Employee #{$error['employee_id']}: {$error['message']}");
        }

        return self::SUCCESS;
    }
}
