<?php

namespace App\Filament\Resources\StockInventoryResource\Pages;

use App\Filament\Resources\StockInventoryResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListStockInventories extends ListRecords
{
    protected static string $resource = StockInventoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
