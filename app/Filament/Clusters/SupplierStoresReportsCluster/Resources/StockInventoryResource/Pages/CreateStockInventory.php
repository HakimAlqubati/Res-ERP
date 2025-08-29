<?php

namespace App\Filament\Clusters\SupplierStoresReportsCluster\Resources\StockInventoryResource\Pages;

use App\Filament\Clusters\SupplierStoresReportsCluster\Resources\StockInventoryResource;
use App\Models\Product;
use App\Services\MultiProductsInventoryService;
use Filament\Actions;
use Filament\Facades\Filament;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateStockInventory extends CreateRecord
{
    protected static string $resource = StockInventoryResource::class; 
   
    public function getInventoryRowData($productId, $unitId, $storeId): array
    {
        $service = new \App\Services\MultiProductsInventoryService(null, $productId, $unitId, $storeId);
        $remainingQty = (float) ($service->getInventoryForProduct($productId)[0]['remaining_qty'] ?? 0);
    
        $unitPrice = \App\Models\UnitPrice::where('product_id', $productId)
            ->where('unit_id', $unitId)
            ->first();
    
        $packageSize = (float) ($unitPrice?->package_size ?? 0);
    
        return [
            'package_size' => $packageSize,
            'remaining_qty' => $remainingQty,
        ];
    }
    

    protected function handleRecordCreation(array $data): Model
    {
        $record = new ($this->getModel())($data);

        if (
            static::getResource()::isScopedToTenant() &&
            ($tenant = Filament::getTenant())
        ) {
            return $this->associateRecordWithTenant($record, $tenant);
        }

        $record->save();

        return $record;
    }
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

}
