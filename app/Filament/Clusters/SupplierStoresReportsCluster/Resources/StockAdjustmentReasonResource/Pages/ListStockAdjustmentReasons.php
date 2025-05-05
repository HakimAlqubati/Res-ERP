<?php

namespace App\Filament\Clusters\SupplierStoresReportsCluster\Resources\StockAdjustmentReasonResource\Pages;

use App\Filament\Clusters\SupplierStoresReportsCluster\Resources\StockAdjustmentReasonResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListStockAdjustmentReasons extends ListRecords
{
    protected static string $resource = StockAdjustmentReasonResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()->icon('heroicon-o-plus-circle'),
        ];
    }
}
