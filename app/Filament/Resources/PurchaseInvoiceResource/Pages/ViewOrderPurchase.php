<?php

namespace App\Filament\Resources\OrderPurchaseResource\Pages;

use App\Filament\Resources\OrderPurchaseResource;
use Filament\Pages\Actions;
use Filament\Resources\Pages\EditRecord;
use Filament\Resources\Pages\ViewRecord;

class ViewOrderPurchase extends ViewRecord
{
    protected static string $resource = OrderPurchaseResource::class;

    protected function getActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
