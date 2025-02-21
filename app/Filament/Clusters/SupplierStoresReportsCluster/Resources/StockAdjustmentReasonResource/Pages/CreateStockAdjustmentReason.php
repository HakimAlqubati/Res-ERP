<?php

namespace App\Filament\Clusters\SupplierStoresReportsCluster\Resources\StockAdjustmentReasonResource\Pages;

use App\Filament\Clusters\SupplierStoresReportsCluster\Resources\StockAdjustmentReasonResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateStockAdjustmentReason extends CreateRecord
{
    protected static string $resource = StockAdjustmentReasonResource::class;
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
