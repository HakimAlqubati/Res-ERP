<?php

namespace App\Filament\Clusters\AreaManagementCluster\Resources\CountryResource\Pages;

use App\Filament\Clusters\AreaManagementCluster\Resources\CountryResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateCountry extends CreateRecord
{
    protected static string $resource = CountryResource::class;
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

}
