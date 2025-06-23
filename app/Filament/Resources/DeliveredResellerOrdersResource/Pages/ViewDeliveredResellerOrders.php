<?php

namespace App\Filament\Resources\DeliveredResellerOrdersResource\Pages;

use App\Filament\Resources\DeliveredResellerOrdersResource;
use Filament\Actions; 
use Filament\Resources\Pages\ViewRecord;

class ViewDeliveredResellerOrders extends ViewRecord
{
    protected static string $resource = DeliveredResellerOrdersResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Actions\DeleteAction::make(),
        ];
    }
}
