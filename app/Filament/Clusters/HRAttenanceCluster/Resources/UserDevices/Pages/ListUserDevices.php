<?php

namespace App\Filament\Clusters\HRAttenanceCluster\Resources\UserDevices\Pages;

use App\Filament\Clusters\HRAttenanceCluster\Resources\UserDevices\UserDeviceResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListUserDevices extends ListRecords
{
    protected static string $resource = UserDeviceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
