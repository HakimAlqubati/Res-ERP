<?php

namespace App\Filament\Clusters\AreaManagementCluster\Resources\CityResource\Pages;

use Filament\Actions\DeleteAction;
use App\Filament\Clusters\AreaManagementCluster\Resources\CityResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditCity extends EditRecord
{
    protected static string $resource = CityResource::class;

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
