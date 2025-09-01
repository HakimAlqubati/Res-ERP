<?php

namespace App\Filament\Resources\AuditResource\Pages;

use Filament\Actions\DeleteAction;
use App\Filament\Resources\AuditResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditAudit extends EditRecord
{
    protected static string $resource = AuditResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
