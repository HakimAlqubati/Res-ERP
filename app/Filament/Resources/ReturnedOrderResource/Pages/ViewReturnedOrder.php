<?php

namespace App\Filament\Resources\ReturnedOrderResource\Pages;

use Filament\Actions\DeleteAction;
use App\Filament\Resources\ReturnedOrderResource;
use App\Models\ReturnedOrder;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewReturnedOrder extends ViewRecord
{
    protected static string $resource = ReturnedOrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
            ->hidden(fn ($record) => $record->status === ReturnedOrder::STATUS_APPROVED),
        ];
    }
}
