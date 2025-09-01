<?php

namespace App\Filament\Clusters\SupplierStoresReportsCluster\Resources\StockAdjustmentReasonResource\Pages;

use Filament\Actions\DeleteAction;
use App\Filament\Clusters\SupplierStoresReportsCluster\Resources\StockAdjustmentReasonResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditStockAdjustmentReason extends EditRecord
{
    protected static string $resource = StockAdjustmentReasonResource::class;

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
