<?php

namespace App\Filament\Clusters\SupplierStoresReportsCluster\Resources\StockAdjustmentReasonResource\Pages;

use Filament\Actions\CreateAction;
use App\Filament\Clusters\SupplierStoresReportsCluster\Resources\StockAdjustmentReasonResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListStockAdjustmentReasons extends ListRecords
{
    protected static string $resource = StockAdjustmentReasonResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->icon('heroicon-o-plus-circle'),
        ];
    }
}
