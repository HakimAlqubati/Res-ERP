<?php

namespace App\Filament\Resources\OrderPurchaseResource\Pages;

use Filament\Actions\ViewAction;
use App\Filament\Resources\OrderPurchaseResource;
use Filament\Pages\Actions;
use Filament\Resources\Pages\EditRecord;

class EditOrderPurchase extends EditRecord
{
    protected static string $resource = OrderPurchaseResource::class;

    protected function getActions(): array
    {
        return [
            ViewAction::make(),
            
        ];
    }
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

}
