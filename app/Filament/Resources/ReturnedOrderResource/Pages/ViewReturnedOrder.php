<?php

namespace App\Filament\Resources\ReturnedOrderResource\Pages;

use Filament\Actions\DeleteAction;
use App\Filament\Resources\ReturnedOrderResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewReturnedOrder extends ViewRecord
{
    protected static string $resource = ReturnedOrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
