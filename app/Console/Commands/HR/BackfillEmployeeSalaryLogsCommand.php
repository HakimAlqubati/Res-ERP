<?php

declare(strict_types=1);

namespace App\Console\Commands\HR;

use App\Modules\HR\SalaryIncrease\Services\EmployeeSalaryBackfillService;
use App\Modules\HR\SalaryIncrease\Support\MultiTenantCommandRunner;
use Illuminate\Console\Command;

class BackfillEmployeeSalaryLogsCommand extends Command
{
    protected $signature = 'hr:salaries:backfill-logs
                            {--dry-run : Preview changes without modifying the database}
                            {--tenant= : Target a specific tenant by ID, name, domain, or database}
                            {--chunk=500 : Number of employees to process per chunk}';

    protected $description = 'Backfill hr_employee_salary_logs across 3 layers (payroll base salary transactions, approved salary increase requests, and current hr_employees.salary reconciliation).';

    public function handle(
        MultiTenantCommandRunner $runner,
        EmployeeSalaryBackfillService $backfillService,
    ): int {
        $dryRun = (bool) $this->option('dry-run');
        $chunkSize = max(50, (int) ($this->option('chunk') ?: 500));

        if ($dryRun) {
            $this->warn('Running in DRY-RUN mode. No database writes will be executed.');
        }

        return $runner->run(
            tenantFilter: $this->option('tenant') ? (string) $this->option('tenant') : null,
            callback: fn (string $label): int => $this->runForContext($label, $backfillService, $dryRun, $chunkSize),
            onError: fn ($tenant, \Throwable $e) => $this->error("Error on tenant [{$tenant->name}]: {$e->getMessage()}"),
        );
    }

    private function runForContext(
        string $label,
        EmployeeSalaryBackfillService $backfillService,
        bool $dryRun,
        int $chunkSize
    ): int {
        $stats = $backfillService->run($dryRun, $chunkSize);

        if ($stats['skipped_reason'] !== null) {
            $this->warn("[{$label}] Skipped: {$stats['skipped_reason']}");

            return self::SUCCESS;
        }

        $this->info(sprintf(
            '[%s] scanned=%d, backfilled=%d, with_salary_changes=%d, skipped_existing=%d, skipped_zero=%d, logs_%s=%d',
            $label,
            $stats['scanned'],
            $stats['backfilled'],
            $stats['with_multi_history'],
            $stats['skipped_existing'],
            $stats['skipped_zero'],
            $dryRun ? 'planned' : 'inserted',
            $stats['logs_to_insert'],
        ));

        return self::SUCCESS;
    }
}
