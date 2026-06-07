<?php

namespace App\Filament\Resources\AdvanceWages\Pages;

use App\Filament\Resources\AdvanceWages\AdvanceWageResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Pages\EditRecord;

class EditAdvanceWage extends EditRecord
{
    protected static string $resource = AdvanceWageResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
            ForceDeleteAction::make(),
            RestoreAction::make(),
        ];
    }
}
