<?php

namespace App\Modules\Stock\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Route;
use App\Modules\Stock\Reports\GrnConsumption\Contracts\GrnConsumptionRepositoryInterface;
use App\Modules\Stock\Reports\GrnConsumption\Repositories\GrnConsumptionRepository;
use App\Modules\Stock\Reports\ProductGrnAggregation\Contracts\ProductAggregationRepositoryInterface;
use App\Modules\Stock\Reports\ProductGrnAggregation\Repositories\ProductAggregationRepository;
use App\Modules\Stock\Reports\FifoBatchReport\Contracts\FifoBatchRepositoryInterface;
use App\Modules\Stock\Reports\FifoBatchReport\Repositories\FifoBatchRepository;

class StockServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Bind the Repository Interface to its Implementation (GRN Level)
        $this->app->bind(
            GrnConsumptionRepositoryInterface::class,
            GrnConsumptionRepository::class
        );

        // Bind the Repository Interface to its Implementation (Product Aggregation Level)
        $this->app->bind(
            ProductAggregationRepositoryInterface::class,
            ProductAggregationRepository::class
        );

        // Bind the Repository Interface to its Implementation (FIFO Batch Report)
        $this->app->bind(
            FifoBatchRepositoryInterface::class,
            FifoBatchRepository::class
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->registerRoutes();
        $this->registerApiRoutes();
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
     * Register the Stock Module API Routes.
     */
    protected function registerApiRoutes(): void
    {
        $apiRoutesPath = __DIR__ . '/../routes/api.php';

        if (file_exists($apiRoutesPath)) {
            Route::middleware(['api', 'auth:api'])
                ->prefix('api/stock')
                ->name('api.stock.')
                ->group($apiRoutesPath);
        }
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

