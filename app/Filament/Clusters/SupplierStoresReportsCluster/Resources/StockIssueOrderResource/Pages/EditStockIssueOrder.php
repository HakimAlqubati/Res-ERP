<?php

namespace App\Filament\Clusters\SupplierStoresReportsCluster\Resources\StockIssueOrderResource\Pages;

use Filament\Actions\DeleteAction;
use App\Filament\Clusters\SupplierStoresReportsCluster\Resources\StockIssueOrderResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditStockIssueOrder extends EditRecord
{
    protected static string $resource = StockIssueOrderResource::class;

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
