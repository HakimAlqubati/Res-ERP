<?php

namespace App\Filament\Clusters\POSIntegration\Resources\PosSales\Pages;

use App\Filament\Clusters\POSIntegration\Resources\PosSales\PosSaleResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditPosSale extends EditRecord
{
    protected static string $resource = PosSaleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
            ForceDeleteAction::make(),
            RestoreAction::make(),
        ];
    }
}
