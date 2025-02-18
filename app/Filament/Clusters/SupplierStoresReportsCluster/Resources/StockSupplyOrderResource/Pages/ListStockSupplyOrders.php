<?php

namespace App\Filament\Clusters\SupplierStoresReportsCluster\Resources\StockSupplyOrderResource\Pages;

use App\Filament\Clusters\SupplierStoresReportsCluster\Resources\StockSupplyOrderResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListStockSupplyOrders extends ListRecords
{
    protected static string $resource = StockSupplyOrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
