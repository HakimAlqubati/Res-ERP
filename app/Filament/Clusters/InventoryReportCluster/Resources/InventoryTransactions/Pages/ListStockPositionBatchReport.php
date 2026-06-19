<?php

namespace App\Filament\Clusters\InventoryReportCluster\Resources\InventoryTransactions\Pages;

use App\Filament\Clusters\InventoryReportCluster\Resources\InventoryTransactions\StockPositionBatchReportResource;
use App\Filament\Traits\HasBackButtonAction;
use Filament\Resources\Pages\ListRecords;

class ListStockPositionBatchReport extends ListRecords
{
    use HasBackButtonAction;
    protected static string $resource = StockPositionBatchReportResource::class;

    protected function getHeaderActions(): array
    {
        return [
        ];
    }
}
