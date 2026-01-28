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

// Calculators
use App\Modules\HR\Payroll\Calculators\RateCalculator;
use App\Modules\HR\Payroll\Calculators\AttendanceDeductionCalculator;
use App\Modules\HR\Payroll\Calculators\OvertimeCalculator;
use App\Modules\HR\Payroll\Calculators\PenaltyCalculator;
use App\Modules\HR\Payroll\Calculators\AllowanceCalculator;
use App\Modules\HR\Payroll\Calculators\AdvanceInstallmentCalculator;
use App\Modules\HR\Payroll\Calculators\MealRequestCalculator;
use App\Modules\HR\Payroll\Calculators\GeneralDeductionCalculator;
use App\Modules\HR\Payroll\Calculators\TransactionBuilder;

class PayrollServiceProvider extends ServiceProvider
{
    /**
     * Register module services.
     */
    public function register(): void
    {
        // ===== Calculators =====
        $this->app->singleton(RateCalculator::class);
        $this->app->singleton(AttendanceDeductionCalculator::class);
        $this->app->singleton(OvertimeCalculator::class);
        $this->app->singleton(PenaltyCalculator::class);
        $this->app->singleton(AllowanceCalculator::class);
        $this->app->singleton(AdvanceInstallmentCalculator::class);
        $this->app->singleton(MealRequestCalculator::class);
        $this->app->singleton(GeneralDeductionCalculator::class);
        $this->app->singleton(TransactionBuilder::class);

        // ===== Repositories =====
        $this->app->singleton(PayrollRepositoryInterface::class, PayrollRepository::class);
        $this->app->singleton(SalaryTransactionRepositoryInterface::class, SalaryTransactionRepository::class);
        $this->app->singleton(PayrollRepository::class);
        $this->app->singleton(SalaryTransactionRepository::class);

        // ===== Services =====
        $this->app->singleton(SalaryCalculatorInterface::class, function ($app) {
            return new SalaryCalculatorService(
                $app->make(RateCalculator::class),
                $app->make(AttendanceDeductionCalculator::class),
                $app->make(OvertimeCalculator::class),
                $app->make(PenaltyCalculator::class),
                $app->make(AllowanceCalculator::class),
                $app->make(AdvanceInstallmentCalculator::class),
                $app->make(MealRequestCalculator::class),
                $app->make(GeneralDeductionCalculator::class),
                $app->make(TransactionBuilder::class),
            );
        });
        $this->app->singleton(SalaryCalculatorService::class, function ($app) {
            return $app->make(SalaryCalculatorInterface::class);
        });

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
