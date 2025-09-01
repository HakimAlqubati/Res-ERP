<?php

namespace App\Filament\Resources\StockTransferOrderResource\Pages;

use App\Models\StockTransferOrder;
use App\Filament\Resources\StockTransferOrderResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateStockTransferOrder extends CreateRecord
{
    protected static string $resource = StockTransferOrderResource::class;
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
    protected function afterCreate(): void
    {
        // الحالة كانت approved؟ نفذ الحركة
        if ($this->record->status === StockTransferOrder::STATUS_APPROVED) {
            $this->record->createInventoryTransactionsFromTransfer();
        }
    }
}
