<?php

namespace App\Services\Warnings;

use App\Models\CustomTenantModel;
use App\Services\Warnings\Contracts\WarningHandler;
use Illuminate\Contracts\Container\Container;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\URL;
use Spatie\Multitenancy\Models\Tenant as SpatieTenant;

final class NotificationOrchestrator
{
    public function __construct(private readonly Container $app) {}

    /** التشغيل على السنترال ثم كل التينانتات */
    public function runAll(array $options = []): array
    {
        [$sentC, $failC] = $this->runOnCentral($options);
        [$sentT, $failT] = $this->runOnTenants($options);

        return [$sentC + $sentT, $failC + $failT];
    }

    /** تشغيل على السنترال فقط */
    public function runOnCentral(array $options = []): array
    {
        $this->leaveTenantContext();
        return $this->runHandlers($options);
    }

    /** تشغيل على جميع التينانتات */
    public function runOnTenants(array $options = []): array
    {
        $ok = 0;
        $fail = 0;
        $sent = 0;
        $failed = 0;
        $originalDb = config('database.connections.mysql.database');

        foreach (CustomTenantModel::query()->cursor() as $tenant) {
            try {
                $this->enterTenantContext($tenant, $originalDb);
                $this->forceHttpsUrl($tenant->domain);

                [$s, $f] = $this->runHandlers($options);
                $sent += $s;
                $failed += $f;
                $ok++;
            } catch (\Throwable) {
                $fail++;
            } finally {
                $this->leaveTenantContext($originalDb);
            }
        }

        return [$sent, $failed];
    }

    /** تشغيل كل الهاندلرات المعرّفة في config */
    public function runHandlers(array $options = []): array
    {
        $totalSent = 0;
        $totalFail = 0;

        // تأكد من أن الـ Eloquent يستخدم الـ connection الحالي
        // هذا ضروري لأن الـ dependencies قد تُحَل قبل تبديل الـ database
        DB::purge('mysql');
        DB::reconnect('mysql');

        foreach (config('notifications.handlers', []) as $handlerClass) {
            try {
                /** @var WarningHandler $handler */
                $handler = $this->app->make($handlerClass);
                $handler->setOptions($options);
                [$s, $f] = $handler->handle();
                $totalSent += $s;
                $totalFail += $f;
            } catch (\Throwable $e) {
                Log::error("Handler {$handlerClass} failed: " . $e->getMessage());
                $totalFail++;
            }
        }

        return [$totalSent, $totalFail];
    }

    /* ====== Internals: tenancy & url ====== */

    private function enterTenantContext(CustomTenantModel $tenant, ?string $fallbackDb = null): void
    {
        // تسجيل التينانت الحالي
        Log::info("[NotificationOrchestrator] Entering tenant: {$tenant->id}, database: {$tenant->database}");

        // دائماً نقوم بتبديل قاعدة البيانات في الـ config أولاً
        if ($tenant->database) {
            config(['database.connections.mysql.database' => $tenant->database]);
            DB::purge('mysql');
            DB::reconnect('mysql');

            // تحقق من التبديل
            $currentDb = config('database.connections.mysql.database');
            Log::info("[NotificationOrchestrator] Switched to DB: {$currentDb}");
        }

        // ثم نحاول استخدام API التينانت إذا متوفر
        if (method_exists($tenant, 'makeCurrent')) {
            $tenant->makeCurrent();
        } elseif (method_exists($tenant, 'switchTo')) {
            $tenant->switchTo($tenant->database);
        }
    }

    private function leaveTenantContext(?string $fallbackDb = null): void
    {
        if (class_exists(SpatieTenant::class) && method_exists(SpatieTenant::class, 'forgetCurrent')) {
            SpatieTenant::forgetCurrent();
        } elseif ($fallbackDb) {
            config(['database.connections.mysql.database' => $fallbackDb]);
            DB::purge('mysql');
            DB::reconnect('mysql');
        }
    }

    private function forceHttpsUrl(?string $domain): void
    {
        $domain = trim((string) $domain);
        if ($domain === '') return;

        $host = preg_replace('#^https?://#', '', $domain);
        $url  = 'https://' . rtrim($host, '/');

        config(['app.url' => $url]);
        app('url')->forceRootUrl($url);
        URL::forceScheme('https');
    }
}
