<?php

namespace App\Filament\Resources\SystemSettingResource\Pages;

use Filament\Actions\CreateAction;
use App\Filament\Resources\SystemSettingResource;
use Filament\Pages\Actions;
use Filament\Resources\Pages\ListRecords;

class ListSystemSettings extends ListRecords
{
    protected static string $resource = SystemSettingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
