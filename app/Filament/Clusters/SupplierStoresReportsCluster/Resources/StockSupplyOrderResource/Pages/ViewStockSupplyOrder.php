<?php

namespace App\Filament\Clusters\SupplierStoresReportsCluster\Resources\StockSupplyOrderResource\Pages;

use Filament\Actions\DeleteAction;
use App\Filament\Clusters\SupplierStoresReportsCluster\Resources\StockSupplyOrderResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewStockSupplyOrder extends ViewRecord
{
    protected static string $resource = StockSupplyOrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
   
}
