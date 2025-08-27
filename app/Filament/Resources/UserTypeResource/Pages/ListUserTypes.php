<?php

namespace App\Filament\Resources\UserTypeResource\Pages;

use Filament\Actions\CreateAction;
use App\Filament\Resources\UserTypeResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListUserTypes extends ListRecords
{
    protected static string $resource = UserTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
