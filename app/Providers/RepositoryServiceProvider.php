<?php

namespace App\Providers;

use App\Repositories\Inventory\StockInventory\Contracts\StockInventoryRepositoryInterface;
use App\Repositories\Inventory\StockInventory\StockInventoryRepository;
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
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
