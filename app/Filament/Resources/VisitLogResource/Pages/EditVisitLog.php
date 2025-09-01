<?php

namespace App\Filament\Resources\VisitLogResource\Pages;

use Filament\Actions\DeleteAction;
use App\Filament\Resources\VisitLogResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditVisitLog extends EditRecord
{
    protected static string $resource = VisitLogResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
