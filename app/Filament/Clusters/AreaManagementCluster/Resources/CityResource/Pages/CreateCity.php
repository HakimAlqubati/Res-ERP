<?php

namespace App\Filament\Clusters\AreaManagementCluster\Resources\CityResource\Pages;

use App\Filament\Clusters\AreaManagementCluster\Resources\CityResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateCity extends CreateRecord
{
    protected static string $resource = CityResource::class;
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

}
