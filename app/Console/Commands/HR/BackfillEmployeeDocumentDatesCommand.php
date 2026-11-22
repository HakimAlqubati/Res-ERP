<?php

declare(strict_types=1);

namespace App\Console\Commands\HR;

use App\Models\CustomTenantModel;
use App\Modules\HR\EmployeeDocuments\Services\DocumentBackfillService;
use App\Modules\HR\EmployeeDocuments\Support\EmployeeFileTypeCatalog;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;
use Spatie\Multitenancy\Models\Tenant;

class BackfillEmployeeDocumentDatesCommand extends Command
{
    protected $signature = 'hr:documents:backfill-dates
                            {--dry-run : Preview changes without modifying the database}
                            {--tenant= : Target a specific tenant by ID, name, or domain}
                            {--enable-has-expiry : Automatically enable has_expiry on file types that have extracted expiry dates}
                            {--chunk=500 : Number of rows to process per chunk}';

    protected $description = 'Backfill issue_date and expiry_date on hr_employee_files from legacy dynamic_field_values without deleting JSON data.';

    public function handle(
        DocumentBackfillService $service,
        EmployeeFileTypeCatalog $catalog,
    ): int {
        $dryRun = (bool) $this->option('dry-run');
        $enableHasExpiry = (bool) $this->option('enable-has-expiry');
        $chunkSize = (int) ($this->option('chunk') ?: 500);
        $tenantFilter = $this->option('tenant');

        if ($dryRun) {
            $this->warn('Running in DRY-RUN mode. No database writes will be executed.');
        }

        if (Tenant::current()) {
            return $this->runForContext(
                label: 'Tenant: ' . (Tenant::current()->name ?? Tenant::current()->getKey()),
                service: $service,
                catalog: $catalog,
                dryRun: $dryRun,
                enableHasExpiry: $enableHasExpiry,
                chunkSize: $chunkSize,
            );
        }

        $failed = 0;

        if ($tenantFilter === null || $tenantFilter === '') {
            if ($this->runForContext('Central', $service, $catalog, $dryRun, $enableHasExpiry, $chunkSize) !== self::SUCCESS) {
                $failed++;
            }
        }

        if (! Schema::hasTable('tenants')) {
            return $failed > 0 ? self::FAILURE : self::SUCCESS;
        }

        $tenantsQuery = CustomTenantModel::query()->where('active', 1);

        if ($tenantFilter !== null && $tenantFilter !== '') {
            $tenantsQuery->where(function ($query) use ($tenantFilter): void {
                if (is_numeric($tenantFilter)) {
                    $query->where('id', (int) $tenantFilter);
                }

                $query->orWhere('name', $tenantFilter)
                    ->orWhere('domain', $tenantFilter)
                    ->orWhere('database', $tenantFilter);
            });
        }

        $tenants = $tenantsQuery->get();

        foreach ($tenants as $tenant) {
            try {
                $tenant->makeCurrent();

                if ($this->runForContext("Tenant: {$tenant->name}", $service, $catalog, $dryRun, $enableHasExpiry, $chunkSize) !== self::SUCCESS) {
                    $failed++;
                }
            } catch (\Throwable $e) {
                $failed++;
                $this->error("Error on tenant [{$tenant->name}]: {$e->getMessage()}");
            } finally {
                Tenant::forgetCurrent();
                $catalog->flushMemory();
            }
        }

        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }

    private function runForContext(
        string $label,
        DocumentBackfillService $service,
        EmployeeFileTypeCatalog $catalog,
        bool $dryRun,
        bool $enableHasExpiry,
        int $chunkSize,
    ): int {
        $catalog->flushMemory();

        if (! Schema::hasTable('hr_employee_files') || ! Schema::hasColumn('hr_employee_files', 'expiry_date')) {
            $this->line("[{$label}] Skipped: hr_employee_files expiry columns not migrated yet.");

            return self::SUCCESS;
        }

        $stats = $service->run(
            dryRun: $dryRun,
            enableHasExpiry: $enableHasExpiry,
            chunkSize: $chunkSize,
        );

        $this->info(sprintf(
            '[%s] scanned=%d, updated=%d, skipped=%d, file_types_enabled=%d',
            $label,
            $stats['scanned'],
            $stats['updated'],
            $stats['skipped'],
            $stats['file_types_enabled'],
        ));

        if ($stats['unmapped_keys'] !== []) {
            $this->warn("[{$label}] Unmapped date field keys found (review manually):");

            foreach ($stats['unmapped_keys'] as $key => $count) {
                $this->line("  - {$key}: {$count} record(s)");
            }
        }

        return self::SUCCESS;
    }
}
