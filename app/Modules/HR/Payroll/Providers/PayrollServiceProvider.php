<?php

namespace App\Modules\HR\Payroll\Providers;

use Illuminate\Support\ServiceProvider;
use App\Models\PayrollRun;
use App\Modules\HR\Payroll\Observers\PayrollRunObserver;
use App\Modules\HR\Payroll\Services\PayrollRunService;
use App\Modules\HR\Payroll\Services\PayrollCalculationService;
use App\Modules\HR\Payroll\Services\PayrollSimulationService;
use App\Modules\HR\Payroll\Services\PayrollFinancialSyncService;
use App\Modules\HR\Payroll\Services\SalaryCalculatorService;
use App\Modules\HR\Payroll\Repositories\PayrollRepository;
use App\Modules\HR\Payroll\Repositories\SalaryTransactionRepository;

class PayrollServiceProvider extends ServiceProvider
{
    /**
     * Register module services.
     */
    public function register(): void
    {
        // Register Repositories
        $this->app->singleton(PayrollRepository::class, function ($app) {
            return new PayrollRepository();
        });

        $this->app->singleton(SalaryTransactionRepository::class, function ($app) {
            return new SalaryTransactionRepository();
        });

        // Register Services
        $this->app->singleton(SalaryCalculatorService::class, function ($app) {
            return new SalaryCalculatorService();
        });

        $this->app->singleton(PayrollSimulationService::class, function ($app) {
            return new PayrollSimulationService(
                $app->make(\App\Services\HR\AttendanceHelpers\Reports\AttendanceFetcher::class),
                $app->make(SalaryCalculatorService::class)
            );
        });

        $this->app->singleton(PayrollCalculationService::class, function ($app) {
            return new PayrollCalculationService(
                $app->make(PayrollRepository::class),
                $app->make(SalaryTransactionRepository::class),
                $app->make(PayrollSimulationService::class)
            );
        });

        $this->app->singleton(PayrollRunService::class, function ($app) {
            return new PayrollRunService(
                $app->make(PayrollCalculationService::class)
            );
        });

        $this->app->singleton(PayrollFinancialSyncService::class, function ($app) {
            return new PayrollFinancialSyncService();
        });
    }

    /**
     * Bootstrap module services.
     */
    public function boot(): void
    {
        // Register Observer for PayrollRun model
        // Note: This uses the module's observer, not the original one
        // PayrollRun::observe(PayrollRunObserver::class);
    }
}
