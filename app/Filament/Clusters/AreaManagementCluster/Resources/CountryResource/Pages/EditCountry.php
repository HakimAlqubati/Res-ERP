<?php

namespace App\Filament\Clusters\AreaManagementCluster\Resources\CountryResource\Pages;

use Filament\Actions\DeleteAction;
use App\Filament\Clusters\AreaManagementCluster\Resources\CountryResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditCountry extends EditRecord
{
    protected static string $resource = CountryResource::class;

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
