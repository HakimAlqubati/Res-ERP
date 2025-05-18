<?php

namespace App\Filament\Resources\StockTransferOrderResource\Pages;

use App\Filament\Resources\StockTransferOrderResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditStockTransferOrder extends EditRecord
{
    protected static string $resource = StockTransferOrderResource::class;

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
