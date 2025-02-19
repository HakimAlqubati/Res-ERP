<?php

namespace App\Filament\Clusters\SupplierStoresReportsCluster\Resources\StockInventoryResource\Pages;

use App\Filament\Clusters\SupplierStoresReportsCluster\Resources\StockInventoryResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateStockInventory extends CreateRecord
{
    protected static string $resource = StockInventoryResource::class; 
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

}
