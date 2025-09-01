<?php

namespace App\Filament\Resources\EquipmentTypeResource\Pages;

use Filament\Actions\CreateAction;
use App\Filament\Resources\EquipmentTypeResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListEquipmentTypes extends ListRecords
{
    protected static string $resource = EquipmentTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
