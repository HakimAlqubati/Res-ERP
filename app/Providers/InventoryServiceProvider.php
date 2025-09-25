<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\Inventory\Contracts\{
    InventoryRepositoryInterface,
    PricingStrategyInterface,
    UnitConversionInterface,
    ThresholdPolicyInterface
};
use App\Services\Inventory\Repositories\EloquentInventoryRepository;
use App\Services\Inventory\Domain\{
    PricingStrategyService,
    UnitConversionService,
    ReorderThresholdPolicy
};

class InventoryServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(InventoryRepositoryInterface::class, EloquentInventoryRepository::class);
        $this->app->bind(PricingStrategyInterface::class, PricingStrategyService::class);
        $this->app->bind(UnitConversionInterface::class, UnitConversionService::class);
        $this->app->bind(ThresholdPolicyInterface::class, ReorderThresholdPolicy::class);
    }

    public function boot(): void
    {
        //
    }
}
