<?php

namespace App\Filament\Clusters\CashBoxAndBank\Resources\CashBoxes\Pages;

use App\Filament\Clusters\CashBoxAndBank\Resources\CashBoxes\CashBoxResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Pages\EditRecord;

class EditCashBox extends EditRecord
{
    protected static string $resource = CashBoxResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
            ForceDeleteAction::make(),
            RestoreAction::make(),
        ];
    }
}
