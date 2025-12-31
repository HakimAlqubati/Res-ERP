<?php

namespace App\Filament\Clusters\SupplierStoresReportsCluster\Resources\StockInventoryResource\Pages;

use App\Filament\Clusters\SupplierStoresReportsCluster\Resources\StockInventoryResource;
use App\Models\Product;
use App\Services\MultiProductsInventoryService;
use Filament\Actions;
use Filament\Facades\Filament;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;

class CreateStockInventory extends CreateRecord
{
    protected static string $resource = StockInventoryResource::class;
    protected static bool $canCreateAnother = false;

    protected array $extraDetails = [];

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
        // dd('sdf', $data,$this->extraDetails);
        $record = new ($this->getModel())($data);

        if (
            static::getResource()::isScopedToTenant() &&
            ($tenant = Filament::getTenant())
        ) {
            return $this->associateRecordWithTenant($record, $tenant);
        }


        $record->save();

        $record->details()->createMany($data['details']);
        return $record;
    }
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // جمع كل الصفحات من الكاش
        $pages       = (array) ($this->data['details_pages'] ?? []);
        $currentPage = (int) ($this->data['current_page'] ?? 1);
        $pageDetails = (array) ($this->data['page_details'] ?? []);

        // ✅ دمج الصفحة الحالية في الـ cache قبل الحفظ
        // هذا يضمن حفظ التعديلات على physical_quantity حتى لو لم يتنقل المستخدم بين الصفحات
        if (!empty($pageDetails)) {
            $pages[$currentPage] = $pageDetails;
        }

        // ترتيب الصفحات ودمجها
        ksort($pages);
        $merged = [];
        foreach ($pages as $rows) {
            foreach ((array) $rows as $row) {
                // نظّف حقول الواجهة قبل الحفظ
                unset($row['rowInventoryCache'], $row['rowUnitsCache']);
                $merged[] = $row;
            }
        }

        $data['details'] = $merged;

        return $data;
    }
}
