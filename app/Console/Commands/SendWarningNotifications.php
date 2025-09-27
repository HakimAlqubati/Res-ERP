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
use Illuminate\Support\Facades\URL;
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
                // $this->setTenantBaseUrl($tenant);   // يضبط الـ app.url على دومين التينانت

                [$s, $f] = $this->runOnce();
                $tenantsSent += $s;
                $tenantsFail += $f;
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

        // جلب المستخدمين وفق الأدوار المطلوبة من قاعدة الاتصال الحالية
        $users = $this->getTenantRecipients();

        // إضافة storekeeper إن وجد
        if ($store->storekeeper instanceof User) {
            $users->push($store->storekeeper);
        }

        // تشذيب وتوحيد
        $users = $this->uniqueUsers($users);

        // تطبيق فلتر --user إن تم تمريره (ID أو Email)
        if ($target = $this->option('user')) {
            $targetId = is_numeric($target) ? (int) $target : $target;
            $users = $users->filter(function (User $u) use ($targetId) {
                return $u->id === $targetId || $u->email === $targetId;
            })->values();
        }

        if ($users->isEmpty()) {
            $this->warn('No users to notify on this connection.');
            return [0, 0];
        }

        // تحديد حد الإرسال
        $limit = (int) $this->option('limit') ?: 100;
        $users = $users->take(max(1, $limit));

        // رابط التقرير
        $reportUrl = MinimumProductQtyReportResource::getUrl('index', [
            'store_id' => $store->id,
        ]);


        // مصنع البايلود
        $payloadFactory = static function (int $storeId): WarningPayload {
            return WarningPayload::make(
                'Inventory Low',
                'Inventory qty is lower',
                WarningLevel::Warning
            )
                ->ctx(['store_id' => $storeId])
                ->scope('lowstock-12-3')
                ->expires(now()->addHours(6));
        };

        foreach ($users as $user) {
            try {
                Warnings::send(
                    $user,
                    $payloadFactory($store->id)->url($reportUrl)
                );
                $sent++;
            } catch (\Throwable $e) {
                $failed++;
                AppLog::write('Stock is low', AppLog::LEVEL_WARNING, 'inventory', [
                    'store_id' => $store?->id,
                    'error'    => $e->getMessage(),
                ]);
            }
        }

        return [$sent, $failed];
    }

    /**
     * يجلب مستلمي التنبيه من قاعدة الاتصال الحالية
     * وفق شرط الأدوار (roles.id IN [1,3]).
     *
     * @return \Illuminate\Support\Collection<\App\Models\User>
     */
    protected function getTenantRecipients(): Collection
    {
        // ملاحظة: نفترض وجود علاقة roles() في موديل User
        // ولا يوجد $connection ثابت يجبره على السنترال.
        $query = User::query()
            ->whereHas('roles', function ($q) {
                $q->whereIn('id', [1, 3]);
            });

        return $query->get()->values();
    }

    /**
     * توحيد المستخدمين على أساس id وإزالة nulls وتحويل قيم غير User إلى User.
     * @param Collection<int, mixed> $users
     * @return Collection<int, User>
     */
    protected function uniqueUsers(Collection $users): Collection
    {
        return $users
            ->map(function ($u) {
                if ($u instanceof User) {
                    return $u;
                }

                if (is_int($u) || (is_string($u) && ctype_digit($u))) {
                    return User::find((int) $u);
                }

                if (is_string($u) && str_contains($u, '@')) {
                    return User::query()->where('email', $u)->first();
                }

                if (is_array($u)) {
                    if (isset($u['id'])) {
                        return User::find((int) $u['id']);
                    }
                    if (isset($u['email'])) {
                        return User::query()->where('email', $u['email'])->first();
                    }
                }

                return null;
            })
            ->filter()
            ->unique(fn(User $u) => $u->id)
            ->values();
    }

    /**
     * دخول سياق التينانت: يفضّل makeCurrent() من Spatie،
     * ولو ما توفّر، يحاول switchTo() إن كنت معرفه بنفسك،
     * وإلا نحرك اتصال mysql يدويًا.
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

        // آخر الحلول: تعديل اتصال mysql مباشرة
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

    protected function setTenantBaseUrl(CustomTenantModel $tenant): void
    {
        $host = preg_replace('#^https?://#', '', (string) $tenant->domain);
        $url  = 'https://' . rtrim($host, '/');

        config(['app.url' => $url]);
        app('url')->forceRootUrl($url);
        URL::forceScheme('https'); // ← مهم جداً
    }
}
