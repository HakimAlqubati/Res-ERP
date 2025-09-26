<?php

namespace App\Console\Commands;

use App\Enums\Warnings\WarningLevel;
use App\Facades\Warnings;
use App\Filament\Clusters\SupplierStoresReportsCluster\Resources\MinimumProductQtyReportResource;
use App\Models\AppLog;
use App\Models\Store;
use App\Models\User;
use App\Models\CustomTenantModel;
use App\Services\Warnings\WarningPayload;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Spatie\Multitenancy\Models\Tenant as SpatieTenant;

class SendWarningNotifications extends Command
{
    protected $signature = 'notifications:warning
                            {--mode=default : default|retry|due|escalate|cleanup}
                            {--user= : Target specific user ID}
                            {--limit=100 : Max notifications to process}';

    protected $description = 'Send warning notifications on CENTRAL first, then ALL tenants. No survivors.';

    public function handle(): int
    {
        $totalSent = 0;
        $totalFail = 0;

        // === CENTRAL ===
        $this->info('=== CENTRAL DB ===');
        $this->leaveTenantContext(); // تأكيد أننا على السنترال
        [$s, $f] = $this->runOnce();
        $this->info("Central: sent={$s}, failed={$f}");
        $totalSent += $s;
        $totalFail += $f;

        // === TENANTS ===
        $this->info('=== TENANTS ===');
        $ok = 0;
        $fail = 0;
        $tenantsSent = 0;
        $tenantsFail = 0;

        $originalDb = config('database.connections.mysql.database');

        foreach (CustomTenantModel::query()->cursor() as $tenant) {
            $db = $tenant->database ?: 'unknown';
            $this->line("-> Tenant [{$tenant->id}] ({$db})");

            try {
                $this->enterTenantContext($tenant, $originalDb);

                [$s, $f] = $this->runOnce();
                $tenantsSent += $s;
                $tenantsFail += $f;
                $ok++;
            } catch (\Throwable $e) {
                $this->error("   failed: " . $e->getMessage());
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
     * المنطق الفعلي للإرسال على الاتصال الحالي.
     * يرجّع [sent, failed]
     */
    protected function runOnce(): array
    {
        $sent = 0;
        $failed = 0;

        // متجر افتراضي (موديل) مع علاقة storekeeper
        $store = Store::query()
            ->defaultStore()
            ->with('storekeeper')
            ->first();

        if (!$store instanceof Store) {
            $this->warn('No default store. Neat.');
            return [0, 0];
        }

        // بناء قائمة المستخدمين
        $users = collect(getAdminsToNotify());

        if ($store->storekeeper instanceof User) {
            $users->push($store->storekeeper);
        }

        // تشذيب
        $users = $this->uniqueUsers($users);

        // رابط التقرير
        $reportUrl = MinimumProductQtyReportResource::getUrl('index', [
            'store_id' => $store->id,
        ]);

        foreach ($users as $user) {
            try {
                Warnings::send(
                    $user,
                    WarningPayload::make(
                        'Inventory Low',
                        'Inventory qty is lower',
                        WarningLevel::Warning
                    )
                        ->ctx(['store_id' => $store->id])
                        ->scope('lowstock-12-3')
                        ->url($reportUrl)
                        ->expires(now()->addHours(6))
                );
                $sent++;
            } catch (\Throwable $e) {
                $failed++;
                AppLog::write('Stock is low', AppLog::LEVEL_WARNING, 'inventory', [
                    'store_id' => $store?->id, // صححنا idate -> id
                    'error'    => $e->getMessage(),
                ]);
            }
        }

        return [$sent, $failed];
    }

    /**
     * توحيد المستخدمين على أساس id وإزالة nulls.
     * @param Collection<int, mixed> $users
     * @return Collection<int, User>
     */
    protected function uniqueUsers(Collection $users): Collection
    {
        return $users
            ->filter()
            ->unique(fn($u) => $u instanceof User ? $u->id : $u)
            ->map(fn($u) => $u instanceof User ? $u : User::find($u))
            ->filter()
            ->values();
    }

    /**
     * دخول سياق التينانت: يفضّل makeCurrent() من Spatie،
     * ولو ما توفّر، يحاول switchTo() إن كنت معرفه بنفسك.
     */
    protected function enterTenantContext(CustomTenantModel $tenant, ?string $fallbackDb = null): void
    {
        if (method_exists($tenant, 'makeCurrent')) {
            $tenant->makeCurrent(); // يفعّل SwitchTenantDatabaseTask
            return;
        }

        if (method_exists(CustomTenantModel::class, 'switchTo')) {
            CustomTenantModel::switchTo($tenant->database);
            return;
        }

        // آخر الحلول: تعديل اتصال mysql مباشرة (بسيط وآمن قدر الإمكان)
        if ($tenant->database) {
            config(['database.connections.mysql.database' => $tenant->database]);
            DB::purge('mysql');
            DB::reconnect('mysql');
        }
    }

    /**
     * الخروج من سياق التينانت والعودة للسنترال.
     */
    protected function leaveTenantContext(?string $fallbackDb = null): void
    {
        if (class_exists(SpatieTenant::class) && method_exists(SpatieTenant::class, 'forgetCurrent')) {
            SpatieTenant::forgetCurrent();
        } elseif ($fallbackDb) {
            config(['database.connections.mysql.database' => $fallbackDb]);
            DB::purge('mysql');
            DB::reconnect('mysql');
        }
    }
}
