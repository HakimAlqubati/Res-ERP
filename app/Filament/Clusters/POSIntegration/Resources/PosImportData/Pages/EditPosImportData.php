<?php

namespace App\Filament\Clusters\POSIntegration\Resources\PosImportData\Pages;

use App\Filament\Clusters\POSIntegration\Resources\PosImportData\PosImportDataResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Pages\EditRecord;

class EditPosImportData extends EditRecord
{
    protected static string $resource = PosImportDataResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
            ForceDeleteAction::make(),
            RestoreAction::make(),
        ];
    }
}
