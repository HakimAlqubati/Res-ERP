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
    public function getCategoryDetails($categoryId, $storeId)
    {
        $products = Product::where('category_id', $categoryId)
            ->where('active', 1)
            ->get();

        $details = [];

        foreach ($products as $product) {
            $unitPrice   = $product->unitPrices()->first();
            $unitId      = $unitPrice?->unit_id;
            $packageSize = (float) ($unitPrice?->package_size ?? 0);

            $service      = new MultiProductsInventoryService(null, $product->id, $unitId, $storeId);
            $remainingQty = (float) ($service->getInventoryForProduct($product->id)[0]['remaining_qty'] ?? 0);

            $details[] = [
                'product_id'        => $product->id,
                'unit_id'           => $unitId,
                'package_size'      => $packageSize,
                'system_quantity'   => $remainingQty,
                'physical_quantity' => $remainingQty,
                'difference'        => 0,
            ];
        }

        // Livewire سيحوّلها تلقائياً إلى JSON
        return $details;
    }

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
