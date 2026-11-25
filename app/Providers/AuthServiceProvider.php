<?php

namespace App\Providers;

use App\Models\CentralSetting;
use App\Models\CustomTenantModel;
use App\Models\Task;
use App\Models\TenantInvoice;
use App\Policies\CentralSettingPolicy;
use App\Policies\TaskPolicy;
use App\Policies\TenantInvoicePolicy;
use App\Policies\TenantPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;
use Spatie\Multitenancy\Models\Tenant;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        // 'App\Models\Model' => 'App\Policies\ModelPolicy',
        Task::class => TaskPolicy::class,
        CustomTenantModel::class => TenantPolicy::class,
        Tenant::class => TenantPolicy::class,
        TenantInvoice::class => TenantInvoicePolicy::class,
        CentralSetting::class => CentralSettingPolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        $this->registerPolicies();

        // Additional authorization logic can be added here
    }
}
