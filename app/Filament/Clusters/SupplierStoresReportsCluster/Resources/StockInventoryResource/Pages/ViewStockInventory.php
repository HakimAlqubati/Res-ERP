<?php

namespace App\Filament\Clusters\SupplierStoresReportsCluster\Resources\StockInventoryResource\Pages;

use App\Filament\Clusters\SupplierStoresReportsCluster\Resources\StockInventoryResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Contracts\Support\Htmlable;

class ViewStockInventory extends ViewRecord
{
    protected static string $resource = StockInventoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Actions\DeleteAction::make(),
        ];
    }
}
