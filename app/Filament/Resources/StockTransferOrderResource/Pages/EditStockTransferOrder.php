<?php

namespace App\Filament\Resources\StockTransferOrderResource\Pages;

use Filament\Actions\DeleteAction;
use App\Filament\Resources\StockTransferOrderResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditStockTransferOrder extends EditRecord
{
    protected static string $resource = StockTransferOrderResource::class;

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
