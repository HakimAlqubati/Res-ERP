<?php

namespace App\Filament\Clusters\SupplierStoresReportsCluster\Resources\StockInventoryResource\Pages;

use App\Filament\Clusters\SupplierStoresReportsCluster\Resources\StockInventoryResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Contracts\Support\Htmlable;

class EditStockInventory extends EditRecord
{
    protected static string $resource = StockInventoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Actions\DeleteAction::make(),
        ];
    }

    public function getTitle(): string | Htmlable
    {
        return 'Finalize';
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getFormActions(): array
    {
        return [
            $this->getSaveFormAction()
                ->disabled(fn() => !($this->data['edit_enabled'] ?? false))
                ->tooltip('Enable editing first to save changes.'),
            $this->getCancelFormAction()->hidden(),
        ];
    }
}