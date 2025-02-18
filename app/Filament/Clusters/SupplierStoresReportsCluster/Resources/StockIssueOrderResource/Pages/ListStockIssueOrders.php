<?php

namespace App\Filament\Clusters\SupplierStoresReportsCluster\Resources\StockIssueOrderResource\Pages;

use App\Filament\Clusters\SupplierStoresReportsCluster\Resources\StockIssueOrderResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListStockIssueOrders extends ListRecords
{
    protected static string $resource = StockIssueOrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
