<?php

namespace App\Filament\Resources\ResellerSaleResource\Pages;

use App\Filament\Resources\ResellerSaleResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListResellerSales extends ListRecords
{
    protected static string $resource = ResellerSaleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
