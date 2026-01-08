<?php

namespace App\Modules\HR\Payroll\Providers;

use Illuminate\Support\ServiceProvider;
use App\Models\PayrollRun;
use App\Modules\HR\Payroll\Observers\PayrollRunObserver;

// Services
use App\Modules\HR\Payroll\Services\PayrollRunService;
use App\Modules\HR\Payroll\Services\PayrollCalculationService;
use App\Modules\HR\Payroll\Services\PayrollSimulationService;
use App\Modules\HR\Payroll\Services\PayrollFinancialSyncService;
use App\Modules\HR\Payroll\Services\SalaryCalculatorService;

// Repositories
use App\Modules\HR\Payroll\Repositories\PayrollRepository;
use App\Modules\HR\Payroll\Repositories\SalaryTransactionRepository;

// Contracts
use App\Modules\HR\Payroll\Contracts\SalaryCalculatorInterface;
use App\Modules\HR\Payroll\Contracts\PayrollRunnerInterface;
use App\Modules\HR\Payroll\Contracts\PayrollSimulatorInterface;
use App\Modules\HR\Payroll\Contracts\PayrollFinancialSyncInterface;
use App\Modules\HR\Payroll\Contracts\PayrollRepositoryInterface;
use App\Modules\HR\Payroll\Contracts\SalaryTransactionRepositoryInterface;

class PayrollServiceProvider extends ServiceProvider
{
    /**
     * Register module services.
     */
    public function register(): void
    {
        // ===== Repositories =====
        $this->app->singleton(PayrollRepositoryInterface::class, PayrollRepository::class);
        $this->app->singleton(SalaryTransactionRepositoryInterface::class, SalaryTransactionRepository::class);

        // Keep concrete class bindings for backward compatibility
        $this->app->singleton(PayrollRepository::class);
        $this->app->singleton(SalaryTransactionRepository::class);

        // ===== Services =====
        $this->app->singleton(SalaryCalculatorInterface::class, SalaryCalculatorService::class);
        $this->app->singleton(SalaryCalculatorService::class);

        $this->app->singleton(PayrollSimulatorInterface::class, function ($app) {
            return new PayrollSimulationService(
                $app->make(\App\Services\HR\AttendanceHelpers\Reports\AttendanceFetcher::class),
                $app->make(SalaryCalculatorInterface::class)
            );
        });
        $this->app->singleton(PayrollSimulationService::class, function ($app) {
            return $app->make(PayrollSimulatorInterface::class);
        });

        $this->app->singleton(PayrollCalculationService::class, function ($app) {
            return new PayrollCalculationService(
                $app->make(PayrollRepositoryInterface::class),
                $app->make(SalaryTransactionRepositoryInterface::class),
                $app->make(PayrollSimulatorInterface::class)
            );
        });

        $this->app->singleton(PayrollRunnerInterface::class, function ($app) {
            return new PayrollRunService(
                $app->make(PayrollCalculationService::class)
            );
        });
        $this->app->singleton(PayrollRunService::class, function ($app) {
            return $app->make(PayrollRunnerInterface::class);
        });

        $this->app->singleton(PayrollFinancialSyncInterface::class, PayrollFinancialSyncService::class);
        $this->app->singleton(PayrollFinancialSyncService::class);
    }

    /**
     * Bootstrap module services.
     */
    public function boot(): void
    {
        // Load module routes
        $this->loadRoutesFrom(__DIR__ . '/../routes.php');

        // Register Observer for PayrollRun model
        // Note: This uses the module's observer, not the original one
        // PayrollRun::observe(PayrollRunObserver::class);
    }
}
