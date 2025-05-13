<?php

namespace App\Providers;

use App\Models\DailyTasksSettingUp;
use App\Models\ReturnedOrder;
use App\Models\Task;
use App\Policies\DailyTasksSettingUpPolicy;
use App\Policies\ReturnedOrderPolicy;
use App\Policies\TaskPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

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
        // ReturnedOrder::class => ReturnedOrderPolicy::class,
        // DailyTasksSettingUp::class => DailyTasksSettingUpPolicy::class,
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
