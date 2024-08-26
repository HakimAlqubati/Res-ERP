<?php

namespace App\Filament\Resources\OrderResource\Pages;

use App\Filament\Resources\TransferOrderResource;
use Filament\Pages\Actions;
use Filament\Resources\Pages\ListRecords;

class ListTransferOrders extends ListRecords
{
    protected static string $resource = TransferOrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    protected function getTableRecordsPerPageSelectOptions(): array
    {
        return [10, 25, 30, 50];
    }
}
