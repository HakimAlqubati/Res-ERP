<?php

namespace App\Filament\Clusters\SupplierStoresReportsCluster\Resources\StockIssueOrderResource\Pages;

use App\Filament\Clusters\SupplierStoresReportsCluster\Resources\StockIssueOrderResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewStockIssueOrder extends ViewRecord
{
    protected static string $resource = StockIssueOrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
 

}
