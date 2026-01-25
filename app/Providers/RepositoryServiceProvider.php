<?php

namespace App\Providers;

use App\Repositories\Inventory\StockInventory\Contracts\StockInventoryRepositoryInterface;
use App\Repositories\Inventory\StockInventory\StockInventoryRepository;
use App\Repositories\Inventory\StockAdjustment\Contracts\StockAdjustmentRepositoryInterface;
use App\Repositories\Inventory\StockAdjustment\StockAdjustmentRepository;
use Illuminate\Support\ServiceProvider;

class RepositoryServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Stock Inventory Repository Binding
        $this->app->bind(
            StockInventoryRepositoryInterface::class,
            StockInventoryRepository::class
        );

        // Stock Adjustment Repository Binding
        $this->app->bind(
            StockAdjustmentRepositoryInterface::class,
            StockAdjustmentRepository::class
        );
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
