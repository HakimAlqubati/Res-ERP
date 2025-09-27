<?php

namespace App\Console\Commands;

use App\Models\CustomTenantModel;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;
use Spatie\Multitenancy\Models\Tenant as SpatieTenant;

class SendWarningNotifications extends Command
{
    protected $signature = 'notifications:warning
                            {--user= : Target specific user ID or email (optional, for testing)}
                            {--limit=100 : Max users to notify per connection}';

    protected $description = 'Send warning notifications on CENTRAL first, then ALL tenants.';

    public function handle(): int
    {
        $totalSent = 0;
        $totalFail = 0;

        // CENTRAL
        $this->info('=== CENTRAL DB ===');
        $this->leaveTenantContext();
        [$s, $f] = $this->runOnce();
        $this->info("Central: sent={$s}, failed={$f}");
        $totalSent += $s;
        $totalFail += $f;

        // TENANTS
        $this->info('=== TENANTS ===');
        $ok = 0; $fail = 0; $tenantsSent = 0; $tenantsFail = 0;
        $originalDb = config('database.connections.mysql.database');

        foreach (CustomTenantModel::query()->cursor() as $tenant) {
            $db = $tenant->database ?: 'unknown';
            $this->line("-> Tenant [{$tenant->id}] ({$db})");
            try {
                $this->enterTenantContext($tenant, $originalDb);
                $this->setTenantBaseUrl($tenant);

                [$s, $f] = $this->runOnce();
                $tenantsSent += $s; $tenantsFail += $f;
                $ok++;
            } catch (\Throwable $e) {
                $this->error('   failed: ' . $e->getMessage());
                $fail++;
            } finally {
                $this->leaveTenantContext($originalDb);
            }
        }

        $this->info("Tenants: ok={$ok}, failed={$fail}, sent={$tenantsSent}, failed_items={$tenantsFail}");
        $totalSent += $tenantsSent;
        $totalFail += $tenantsFail;

        $this->info("TOTAL: sent={$totalSent}, failed_items={$totalFail}");
        return $totalFail > 0 ? self::FAILURE : self::SUCCESS;
    }

    /**
     * يشغّل جميع الـ Handlers المسجّلة في هذا الاتصال (سنترال/تينانت).
     * يرجّع [sent, failed]
     */
    protected function runOnce(): array
    {
        $totalSent = 0; $totalFail = 0;

        foreach (config('notifications.handlers', []) as $handlerClass) {
            /** @var \App\Services\Warnings\Contracts\WarningHandler $handler */
            $handler = app($handlerClass);

            // خيارات اختيارية من الأمر (للاختبار/الفلترة)
            $handler->setOptions([
                'user'  => $this->option('user'),
                'limit' => (int)($this->option('limit') ?: 100),
            ]);

            [$s, $f] = $handler->handle(); // كل Handler يرجّع [sent, failed]
            $totalSent += $s; $totalFail += $f;
        }

        return [$totalSent, $totalFail];
    }

    /** دخول سياق التينانت */
    protected function enterTenantContext(CustomTenantModel $tenant, ?string $fallbackDb = null): void
    {
        if (method_exists($tenant, 'makeCurrent')) {
            $tenant->makeCurrent();
            return;
        }

        if (method_exists($tenant, 'switchTo')) {
            $tenant->switchTo($tenant->database);
            return;
        }

        if ($tenant->database) {
            config(['database.connections.mysql.database' => $tenant->database]);
            DB::purge('mysql'); DB::reconnect('mysql');
        }
    }

    /** الخروج من سياق التينانت */
    protected function leaveTenantContext(?string $fallbackDb = null): void
    {
        if (class_exists(SpatieTenant::class) && method_exists(SpatieTenant::class, 'forgetCurrent')) {
            SpatieTenant::forgetCurrent();
        } elseif ($fallbackDb) {
            config(['database.connections.mysql.database' => $fallbackDb]);
            DB::purge('mysql'); DB::reconnect('mysql');
        }
    }

    /** ضبط app.url للتيـنـانت إن وُجد دومين */
    protected function setTenantBaseUrl(CustomTenantModel $tenant): void
    {
        $domain = trim((string) $tenant->domain);
        if ($domain === '') {
            $this->warn("Tenant {$tenant->id} has no domain; skipping app.url override.");
            return;
        }

        $hasScheme = str_starts_with($domain, 'http://') || str_starts_with($domain, 'https://');
        $scheme = config('app.force_https', false) ? 'https://' : 'http://';
        $host = $hasScheme ? preg_replace('#^https?://#', '', $domain) : $domain;
        $url  = rtrim(($hasScheme ? (str_starts_with($domain, 'https://') ? 'https://' : 'http://') : $scheme) . $host, '/');

        config(['app.url' => $url]);
        app('url')->forceRootUrl($url);
        if (str_starts_with($url, 'https://') || config('app.force_https', false)) {
            URL::forceScheme('https');
        }
    }
}
