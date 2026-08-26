<?php

namespace App\Modules\Stock\Providers;

use App\Modules\Stock\Reports\FifoBatchReports\Contracts\InventoryStockRepositoryInterface;
use App\Modules\Stock\Reports\FifoBatchReports\Contracts\FifoAllocatorInterface;
use App\Modules\Stock\Reports\FifoBatchReports\Repositories\InventoryStockRepository;
use App\Modules\Stock\Reports\FifoBatchReports\Allocator\FifoAllocationService;
use App\Modules\Stock\Reports\FifoBatchReports\Contracts\GetAvailableStockBatchesQueryInterface;
use App\Modules\Stock\Reports\FifoBatchReports\Queries\GetAvailableStockBatchesQuery;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Route;
use App\Modules\Stock\Reports\GrnConsumption\Contracts\GrnConsumptionRepositoryInterface;
use App\Modules\Stock\Reports\GrnConsumption\Repositories\GrnConsumptionRepository;
use App\Modules\Stock\Reports\ProductGrnAggregation\Contracts\ProductAggregationRepositoryInterface;
use App\Modules\Stock\Reports\ProductGrnAggregation\Repositories\ProductAggregationRepository;
use App\Modules\Stock\Reports\StockBalanceReport\Contracts\StockBalanceRepositoryInterface;
use App\Modules\Stock\Reports\StockBalanceReport\Repositories\StockBalanceRepository;
use App\Modules\Stock\PriceValidation\Providers\PriceValidationServiceProvider;

class StockServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Register sub-module providers.
        $this->app->register(PriceValidationServiceProvider::class);
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

        // ربط الواجهة بالكلاس المسؤول عن الاستعلام (Query Object)
        // استخدمنا bind لأن الكلاس لا يخزن حالة (Stateless) ويقوم بعمليات قراءة فقط
        $this->app->bind(
            GetAvailableStockBatchesQueryInterface::class,
            GetAvailableStockBatchesQuery::class
        );
        
         $this->app->bind(
        InventoryStockRepositoryInterface::class,
        InventoryStockRepository::class,
    );

        // Bind the FIFO Allocator Interface to its Implementation
        $this->app->bind(
            FifoAllocatorInterface::class,
            FifoAllocationService::class,
        );

        $this->app->bind(
            StockBalanceRepositoryInterface::class,
            StockBalanceRepository::class
        );

        $this->app->bind(
            \App\Modules\Stock\Reports\OrderTransfersReports\Interfaces\OrderTransferReportRepositoryInterface::class,
            \App\Modules\Stock\Reports\OrderTransfersReports\Repositories\OrderTransferReportRepository::class
        );

        $this->app->bind(
            \App\Modules\Stock\Reports\StockInventoryValuationReport\Contracts\StockInventoryValuationRepositoryInterface::class,
            \App\Modules\Stock\Reports\StockInventoryValuationReport\Repositories\StockInventoryValuationRepository::class
        );

        $this->app->bind(
            \App\Modules\Stock\Reports\StockInventoryValuationReport\Contracts\StockInventoryValuationServiceInterface::class,
            \App\Modules\Stock\Reports\StockInventoryValuationReport\Services\StockInventoryValuationReportService::class
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
        Route::middleware(['api', 'auth:api'])
            ->prefix('api/stock')
            ->name('api.stock.')
            ->group(__DIR__ . '/../routes/api.php');
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
