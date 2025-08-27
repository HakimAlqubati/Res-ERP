<?php

namespace App\Filament\Resources\ReturnedOrderResource\Pages;

use Filament\Actions\CreateAction;
use App\Filament\Resources\ReturnedOrderResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListReturnedOrders extends ListRecords
{
    protected static string $resource = ReturnedOrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
