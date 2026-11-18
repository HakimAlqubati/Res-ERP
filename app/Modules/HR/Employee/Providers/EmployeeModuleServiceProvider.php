<?php

declare(strict_types=1);

namespace App\Modules\HR\Employee\Providers;

use App\Modules\HR\Employee\Actions\RevokeUserSessionsAction;
use App\Modules\HR\Employee\Contracts\UserSessionRevokerInterface;
use App\Modules\HR\Employee\Events\EmployeeAccessRevoked;
use App\Modules\HR\Employee\Listeners\RevokeSessionsOnTerminationListener;
use App\Modules\HR\Employee\Services\AuthEligibilityService;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

final class EmployeeModuleServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(
            UserSessionRevokerInterface::class,
            RevokeUserSessionsAction::class
        );

        $this->app->singleton(AuthEligibilityService::class);
    }

    public function boot(): void
    {
        Event::listen(
            EmployeeAccessRevoked::class,
            [RevokeSessionsOnTerminationListener::class, 'handle']
        );
    }
}
