<?php

namespace App\Filament\Resources\StockTransferOrderResource\Pages;

use Filament\Actions\CreateAction;
use App\Filament\Resources\StockTransferOrderResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListStockTransferOrders extends ListRecords
{
    protected static string $resource = StockTransferOrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
