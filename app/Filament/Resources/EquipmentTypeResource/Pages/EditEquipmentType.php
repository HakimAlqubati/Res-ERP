<?php

namespace App\Filament\Resources\EquipmentTypeResource\Pages;

use Filament\Actions\DeleteAction;
use App\Filament\Resources\EquipmentTypeResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditEquipmentType extends EditRecord
{
    protected static string $resource = EquipmentTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
