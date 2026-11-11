<?php

namespace App\Filament\Resources\ProductResource\Pages;

use Filament\Notifications\Notification;
use App\Filament\Resources\ProductResource\Support\ProductResourceActions;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\RestoreAction;
use App\Models\ProductPriceHistory;
use Carbon\Carbon;
use App\Filament\Resources\ProductResource;
use Filament\Pages\Actions;
use Filament\Resources\Pages\EditRecord;

class EditProduct extends EditRecord
{
    protected static string $resource = ProductResource::class;


    protected function mutateFormDataBeforeFill(array $data): array
    {
        // $data['name'] = isset($data['name'][app()->getLocale()]) ? $data['name'][app()->getLocale()] : '';
        // $data['description'] =  isset($data['description'][app()->getLocale()]) ? $data['description'][app()->getLocale()] : '';

        return $data;
    }

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
            DeleteAction::make(),
            // Actions\ForceDeleteAction::make(),
            RestoreAction::make(),
            // ...
        ];
    }
    protected function getRedirectUrl(): string
    {
        if(isHakimOrAdel()){
            return $this->getResource()::getUrl('edit', [
                'record' => $this->record->id,
            ]);
        }
        return $this->getResource()::getUrl('index');
    }

    protected function beforeSave(): void
    {
        $productItems = $this->data['productItems'] ?? [];
        if (! empty($productItems)) {
            $error = ProductResourceActions::validateProductItems($productItems, $this->record->id);
            if ($error) {
                Notification::make()
                    ->title($error)
                    ->danger()
                    ->send();

                $this->halt();
            }
        }
    }

    protected function afterSave(): void
    {
        \App\Filament\Resources\ProductResource\Support\ProductResourceActions::recalculateManufacturingProductUnitPrices($this->record);
    }

    public static function logPriceChange(
        int $productId,
        ?int $productItemId,
        int $unitId,
        float $oldPrice,
        float $newPrice,
        string $note = '',
        string $sourceType = 'auto_update',
        ?int $sourceId = null
    ): void {
        ProductPriceHistory::create([
            'product_id' => $productId,
            'product_item_id' => $productItemId,
            'unit_id' => $unitId,
            'old_price' => $oldPrice,
            'new_price' => $newPrice,
            'note' => $note,
            'source_type' => $sourceType,
            'source_id' => $sourceId,
            'date' => Carbon::now(),
        ]);
    }
}
