<?php

namespace App\Filament\Resources\ResellerSaleResource\Pages;

use Filament\Actions\DeleteAction;
use App\Filament\Resources\ResellerSaleResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditResellerSale extends EditRecord
{
    protected static string $resource = ResellerSaleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}