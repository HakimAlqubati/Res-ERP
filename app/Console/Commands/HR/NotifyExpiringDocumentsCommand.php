<?php

declare(strict_types=1);

namespace App\Console\Commands\HR;

use App\Models\AppLog;
use App\Models\CustomTenantModel;
use App\Modules\HR\EmployeeDocuments\Notifications\DocumentExpiryNotifier;
use App\Modules\HR\EmployeeDocuments\Support\EmployeeFileTypeCatalog;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\URL;
use Spatie\Multitenancy\Models\Tenant;

class NotifyExpiringDocumentsCommand extends Command
{
    protected $signature = 'hr:notify-expiring-documents
                            {--tenant= : Target a specific tenant by ID, name, or domain}
                            {--user= : Target a specific recipient by user ID or email (for testing)}
                            {--dry-run : Compute digests without dispatching notifications}
                            {--force : Ignore daily deduplication check}';

    protected $description = 'Send daily summary notifications for expired and expiring-soon employee documents across central and active tenants.';

    public function handle(
        DocumentExpiryNotifier $notifier,
        EmployeeFileTypeCatalog $catalog,
    ): int {
        $dryRun = (bool) $this->option('dry-run');
        $force = (bool) $this->option('force');
        $onlyUser = $this->option('user');
        $tenantFilter = $this->option('tenant');

        if ($dryRun) {
            $this->warn('Running in DRY-RUN mode. No notifications will be dispatched.');
        }

        if (Tenant::current()) {
            return $this->runForContext(
                label: 'Tenant: ' . (Tenant::current()->name ?? Tenant::current()->getKey()),
                notifier: $notifier,
                catalog: $catalog,
                onlyUser: $onlyUser,
                dryRun: $dryRun,
                force: $force,
            );
        }

        $failed = 0;

        if ($tenantFilter === null || $tenantFilter === '') {
            if ($this->runForContext('Central', $notifier, $catalog, $onlyUser, $dryRun, $force) !== self::SUCCESS) {
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

        $originalAppUrl = (string) config('app.url');

        foreach ($tenantsQuery->get() as $tenant) {
            try {
                $tenant->makeCurrent();
                $this->applyTenantUrl($tenant->domain);

                if ($this->runForContext("Tenant: {$tenant->name}", $notifier, $catalog, $onlyUser, $dryRun, $force) !== self::SUCCESS) {
                    $failed++;
                }
            } catch (\Throwable $e) {
                $failed++;
                $this->error("Error on tenant [{$tenant->name}]: {$e->getMessage()}");

                AppLog::write(
                    message: "Error notifying expiring documents for tenant [{$tenant->name}]: {$e->getMessage()}",
                    level: AppLog::LEVEL_ERROR,
                    context: 'HR_DOCUMENT_EXPIRY_NOTIFY',
                    extra: ['tenant' => $tenant->name]
                );
            } finally {
                Tenant::forgetCurrent();
                $catalog->flushMemory();
                $this->restoreAppUrl($originalAppUrl);
            }
        }

        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }

    private function runForContext(
        string $label,
        DocumentExpiryNotifier $notifier,
        EmployeeFileTypeCatalog $catalog,
        string|int|null $onlyUser,
        bool $dryRun,
        bool $force,
    ): int {
        $catalog->flushMemory();

        if (! Schema::hasTable('hr_employee_files') || ! Schema::hasColumn('hr_employee_files', 'expiry_date')) {
            $this->line("[{$label}] Skipped: hr_employee_files expiry columns not migrated yet.");

            return self::SUCCESS;
        }

        $stats = $notifier->notifyForCurrentTenant(
            onlyUser: $onlyUser,
            dryRun: $dryRun,
            force: $force,
        );

        $this->info(sprintf(
            '[%s] expired=%d, expiring_soon=%d, sent=%d, skipped=%d, failed=%d',
            $label,
            $stats['expired'],
            $stats['expiring_soon'],
            $stats['sent'],
            $stats['skipped'],
            $stats['failed'],
        ));

        return $stats['failed'] > 0 ? self::FAILURE : self::SUCCESS;
    }

    private function applyTenantUrl(?string $domain): void
    {
        $domain = trim((string) $domain);

        if ($domain === '') {
            return;
        }

        $host = preg_replace('#^https?://#', '', $domain);
        $url = 'https://' . rtrim((string) $host, '/');

        config(['app.url' => $url]);
        app('url')->forceRootUrl($url);
        URL::forceScheme('https');
    }

    private function restoreAppUrl(string $originalUrl): void
    {
        if ($originalUrl === '') {
            return;
        }

        config(['app.url' => $originalUrl]);
        app('url')->forceRootUrl($originalUrl);
    }
}
