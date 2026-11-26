<?php

declare(strict_types=1);

namespace App\Modules\HR\SalaryIncrease\Providers;

use App\Modules\HR\SalaryIncrease\Contracts\EmployeeSalaryLogServiceInterface;
use App\Modules\HR\SalaryIncrease\Contracts\SalaryIncreaseRepositoryInterface;
use App\Modules\HR\SalaryIncrease\Contracts\SalaryIncreaseServiceInterface;
use App\Modules\HR\SalaryIncrease\Repositories\SalaryIncreaseRepository;
use App\Modules\HR\SalaryIncrease\Rules\SalaryChangeGuard;
use App\Modules\HR\SalaryIncrease\Rules\SalaryIncreaseValidationRule;
use App\Modules\HR\SalaryIncrease\Services\EmployeeSalaryBackfillService;
use App\Modules\HR\SalaryIncrease\Services\EmployeeSalaryLogService;
use App\Modules\HR\SalaryIncrease\Services\SalaryIncreaseService;
use App\Modules\HR\SalaryIncrease\Support\EmployeeSalaryObserverHandler;
use App\Modules\HR\SalaryIncrease\Support\MultiTenantCommandRunner;
use Illuminate\Support\ServiceProvider;

final class SalaryIncreaseServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(SalaryChangeGuard::class);
        $this->app->singleton(EmployeeSalaryBackfillService::class);
        $this->app->singleton(MultiTenantCommandRunner::class);
        $this->app->singleton(EmployeeSalaryObserverHandler::class);

        $this->app->singleton(
            EmployeeSalaryLogServiceInterface::class,
            EmployeeSalaryLogService::class
        );

        $this->app->bind(
            SalaryIncreaseRepositoryInterface::class,
            SalaryIncreaseRepository::class
        );

        $this->app->bind(
            SalaryIncreaseServiceInterface::class,
            SalaryIncreaseService::class
        );

        $this->app->singleton(SalaryIncreaseValidationRule::class);
    }

    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__ . '/../routes.php');
    }

    /**
     * @return array<int, class-string>
     */
    public function provides(): array
    {
        return [
            EmployeeSalaryLogServiceInterface::class,
            SalaryIncreaseRepositoryInterface::class,
            SalaryIncreaseServiceInterface::class,
        ];
    }
}
