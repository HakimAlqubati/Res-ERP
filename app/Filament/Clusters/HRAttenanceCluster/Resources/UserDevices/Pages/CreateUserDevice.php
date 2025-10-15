<?php

namespace App\Filament\Clusters\HRAttenanceCluster\Resources\UserDevices\Pages;

use App\Filament\Clusters\HRAttenanceCluster\Resources\UserDevices\UserDeviceResource;
use Filament\Resources\Pages\CreateRecord;

class CreateUserDevice extends CreateRecord
{
    protected static string $resource = UserDeviceResource::class;
        protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

}
