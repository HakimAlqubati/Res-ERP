<?php

namespace App\Filament\Clusters\SupplierStoresReportsCluster\Resources\InventoryResource\Pages;

use App\Filament\Clusters\SupplierStoresReportsCluster\Resources\InventoryResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateInventory extends CreateRecord
{
    protected static string $resource = InventoryResource::class;
}
