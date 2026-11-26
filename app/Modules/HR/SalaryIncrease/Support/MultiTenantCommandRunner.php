<?php

declare(strict_types=1);

namespace App\Modules\HR\SalaryIncrease\Support;

use App\Models\CustomTenantModel;
use Closure;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Schema;
use Spatie\Multitenancy\Models\Tenant;
use Throwable;

/**
 * Reusable runner for multi-tenant HR Artisan commands.
 * Encapsulates current-tenant detection, `--tenant=` filtering, safe context switching,
 * and clean `forgetCurrent()` teardown so CLI commands stay focused on their domain logic.
 */
final class MultiTenantCommandRunner
{
    /**
     * Execute `$callback(string $label): int` across the current tenant, or Central + matching active tenants.
     *
     * @param  Closure(string): int  $callback
     * @param  Closure(CustomTenantModel, Throwable): void|null  $onError
     */
    public function run(
        ?string $tenantFilter,
        Closure $callback,
        ?Closure $onError = null
    ): int {
        if (Tenant::current()) {
            $label = 'Tenant: '.(Tenant::current()->name ?? Tenant::current()->getKey());

            return $callback($label);
        }

        $failed = 0;
        $hasFilter = $tenantFilter !== null && $tenantFilter !== '';

        if (! $hasFilter && $callback('Central') !== Command::SUCCESS) {
            $failed++;
        }

        if (! Schema::hasTable('tenants')) {
            return $failed > 0 ? Command::FAILURE : Command::SUCCESS;
        }

        $tenants = $this->resolveTenants($tenantFilter);

        foreach ($tenants as $tenant) {
            try {
                $tenant->makeCurrent();

                if ($callback("Tenant: {$tenant->name}") !== Command::SUCCESS) {
                    $failed++;
                }
            } catch (Throwable $e) {
                $failed++;
                if ($onError !== null) {
                    $onError($tenant, $e);
                }
            } finally {
                Tenant::forgetCurrent();
            }
        }

        return $failed > 0 ? Command::FAILURE : Command::SUCCESS;
    }

    /**
     * @return Collection<int, CustomTenantModel>
     */
    private function resolveTenants(?string $tenantFilter)
    {
        $query = CustomTenantModel::query()->where('active', 1);

        if ($tenantFilter !== null && $tenantFilter !== '') {
            $query->where(function ($sub) use ($tenantFilter): void {
                if (is_numeric($tenantFilter)) {
                    $sub->where('id', (int) $tenantFilter);
                }

                $sub->orWhere('name', $tenantFilter)
                    ->orWhere('domain', $tenantFilter)
                    ->orWhere('database', $tenantFilter);
            });
        }

        return $query->get();
    }
}
