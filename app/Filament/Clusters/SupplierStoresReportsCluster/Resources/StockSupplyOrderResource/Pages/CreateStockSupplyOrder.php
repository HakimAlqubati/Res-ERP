<?php

namespace App\Filament\Clusters\SupplierStoresReportsCluster\Resources\StockSupplyOrderResource\Pages;

use App\Filament\Clusters\SupplierStoresReportsCluster\Resources\StockSupplyOrderResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateStockSupplyOrder extends CreateRecord
{
    protected static string $resource = StockSupplyOrderResource::class;
}
