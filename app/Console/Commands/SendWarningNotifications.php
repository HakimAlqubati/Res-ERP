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

        // 1) CENTRAL
        $this->info('=== CENTRAL DB ===');
        [$s, $f] = $this->runOnce();
        $this->info("Central: sent={$s}, failed={$f}");
        $totalSent += $s;
        $totalFail += $f;

        // 2) TENANTS
        $this->info('=== TENANTS ===');
        $originalDb = config('database.connections.mysql.database');

        $ok = 0;
        $fail = 0;
        $tenantsSent = 0;
        $tenantsFail = 0;

        foreach (CustomTenantModel::all() as $tenant) {
            $db = $tenant->database ?: 'unknown';
            $this->line("-> Tenant [{$tenant->id}] ({$db})");

            try {
                // بدّل الاتصال لقاعدة هذا التينانت
                CustomTenantModel::switchTo($db);

                [$s, $f] = $this->runOnce();
                $tenantsSent += $s;
                $tenantsFail += $f;
                $ok++;
            } catch (\Throwable $e) {
                $this->error("   failed: " . $e->getMessage());
                $fail++;
            } finally {
                // ارجع للسنترال بعد كل تينانت
                if ($originalDb) {
                    CustomTenantModel::switchTo($originalDb);
                }
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

        // متجر افتراضي
        $store = Store::query()
            ->defaultStore()          // scopeDefaultStore
            ->with('storekeeper')
            ->first();
        if (!$store) {
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
            ->map(function ($u) {
                return $u instanceof User ? $u : User::find($u);
            })
            ->filter()
            ->values();
    }
}
