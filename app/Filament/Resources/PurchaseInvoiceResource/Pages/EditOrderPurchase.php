<?php

namespace App\Filament\Resources\OrderPurchaseResource\Pages;

use App\Filament\Resources\OrderPurchaseResource;
use Filament\Pages\Actions;
use Filament\Resources\Pages\EditRecord;

class EditOrderPurchase extends EditRecord
{
    protected static string $resource = OrderPurchaseResource::class;

    protected function getActions(): array
    {
        return [
            Actions\ViewAction::make(),
            
        ];
    }
}
