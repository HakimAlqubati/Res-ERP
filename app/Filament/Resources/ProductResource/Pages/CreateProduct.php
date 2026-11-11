<?php

namespace App\Filament\Resources\ProductResource\Pages;

use Filament\Notifications\Notification;
use App\Filament\Resources\ProductResource;
use Filament\Pages\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateProduct extends CreateRecord
{
    protected static string $resource = ProductResource::class;

    protected function beforeCreate(): void
    {
        $units = $this->data['units'] ?? $this->data['allUnitPrices'] ?? [];
        if (!empty($units)) {
            $packageSizes = collect($units)
                ->filter(fn($unit) => ($unit['usage_scope'] ?? 'all') !== \App\Models\UnitPrice::USAGE_NONE)
                ->pluck('package_size')
                ->filter(fn($value) => $value !== null && $value !== '')
                ->map(fn($value) => floatval($value));

            if ($packageSizes->isNotEmpty() && $packageSizes->min() > 1.0) {
                Notification::make()
                    ->title(__('lang.smallest_package_size_greater_than_one'))
                    ->danger()
                    ->send();

                $this->halt();
            }
        }

        $productItems = $this->data['productItems'] ?? [];
        if (!empty($productItems)) {
            $error = \App\Filament\Resources\ProductResource\Support\ProductResourceActions::validateProductItems($productItems, null);
            if ($error) {
                Notification::make()
                    ->title($error)
                    ->danger()
                    ->send();

                $this->halt();
            }
        }
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        return $data;
    }
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function afterCreate(): void
    {
        \App\Filament\Resources\ProductResource\Support\ProductResourceActions::recalculateManufacturingProductUnitPrices($this->record);
    }
}
