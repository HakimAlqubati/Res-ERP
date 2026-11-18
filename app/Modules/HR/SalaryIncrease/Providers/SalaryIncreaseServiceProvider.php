<?php

declare(strict_types=1);

namespace App\Modules\HR\SalaryIncrease\Providers;

use App\Modules\HR\SalaryIncrease\Contracts\SalaryIncreaseRepositoryInterface;
use App\Modules\HR\SalaryIncrease\Contracts\SalaryIncreaseServiceInterface;
use App\Modules\HR\SalaryIncrease\Repositories\SalaryIncreaseRepository;
use App\Modules\HR\SalaryIncrease\Rules\SalaryIncreaseValidationRule;
use App\Modules\HR\SalaryIncrease\Services\SalaryIncreaseService;
use Illuminate\Support\ServiceProvider;

final class SalaryIncreaseServiceProvider extends ServiceProvider
{
    public function register(): void
    {
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
            SalaryIncreaseRepositoryInterface::class,
            SalaryIncreaseServiceInterface::class,
        ];
    }
}
