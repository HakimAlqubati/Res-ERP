<?php

declare(strict_types=1);

namespace App\Modules\HR\EmployeeApplications\Providers;

use App\Modules\HR\EmployeeApplications\Rules\BranchResolution\DefaultEmployeeBranchRule;
use App\Modules\HR\EmployeeApplications\Rules\BranchResolution\ExplicitBranchRule;
use App\Modules\HR\EmployeeApplications\Rules\BranchResolution\HistoricalAttendanceBranchRule;
use App\Modules\HR\EmployeeApplications\Rules\Notifications\BranchManagerByApplicationBranchRule;
use App\Modules\HR\EmployeeApplications\Rules\Notifications\DefaultEmployeeManagerRule;
use App\Modules\HR\EmployeeApplications\Services\ApplicationApproverResolver;
use App\Modules\HR\EmployeeApplications\Services\ApplicationBranchResolver;
use Illuminate\Support\ServiceProvider;

final class EmployeeApplicationServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(ApplicationBranchResolver::class, function ($app) {
            return new ApplicationBranchResolver([
                $app->make(HistoricalAttendanceBranchRule::class),
                $app->make(ExplicitBranchRule::class),
                $app->make(DefaultEmployeeBranchRule::class),
            ]);
        });

        $this->app->singleton(ApplicationApproverResolver::class, function ($app) {
            return new ApplicationApproverResolver([
                $app->make(BranchManagerByApplicationBranchRule::class),
                $app->make(DefaultEmployeeManagerRule::class),
            ]);
        });
    }
}
