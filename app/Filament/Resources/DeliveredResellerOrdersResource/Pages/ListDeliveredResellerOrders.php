<?php

namespace App\Filament\Resources\DeliveredResellerOrdersResource\Pages;

use App\Filament\Resources\DeliveredResellerOrdersResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListDeliveredResellerOrders extends ListRecords
{
    protected static string $resource = DeliveredResellerOrdersResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
