<?php

namespace App\Filament\Clusters\AreaManagementCluster\Resources\DistrictResource\Pages;

use App\Filament\Clusters\AreaManagementCluster\Resources\DistrictResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateDistrict extends CreateRecord
{
    protected static string $resource = DistrictResource::class;
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

}
