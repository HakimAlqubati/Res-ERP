<?php

namespace App\Modules\Stock\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Route;
use App\Modules\Stock\Reports\GrnConsumption\Contracts\GrnConsumptionRepositoryInterface;
use App\Modules\Stock\Reports\GrnConsumption\Repositories\GrnConsumptionRepository;

class StockServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Bind the Repository Interface to its Implementation
        $this->app->bind(
            GrnConsumptionRepositoryInterface::class,
            GrnConsumptionRepository::class
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->registerRoutes();
        $this->registerViews();
    }

    /**
     * Register the Stock Module Routes.
     */
    protected function registerRoutes(): void
    {
        Route::middleware(['web']) // You can add 'auth' middleware here if needed
            ->prefix('stock')
            ->name('stock.')
            ->group(__DIR__ . '/../routes/web.php');
    }

    /**
     * Register the Stock Module Views.
     */
    protected function registerViews(): void
    {
        // This will allow us to use views like: view('stock::reports.index')
        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'stock');
    }
}
