<?php

namespace App\Filament\Resources\ReturnedOrderResource\Pages;

use App\Filament\Resources\ReturnedOrderResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditReturnedOrder extends EditRecord
{
    protected static string $resource = ReturnedOrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
