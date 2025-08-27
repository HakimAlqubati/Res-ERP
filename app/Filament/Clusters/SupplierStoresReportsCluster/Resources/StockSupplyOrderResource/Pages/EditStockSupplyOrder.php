<?php

namespace App\Filament\Clusters\SupplierStoresReportsCluster\Resources\StockSupplyOrderResource\Pages;

use Filament\Actions\DeleteAction;
use App\Filament\Clusters\SupplierStoresReportsCluster\Resources\StockSupplyOrderResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditStockSupplyOrder extends EditRecord
{
    protected static string $resource = StockSupplyOrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
