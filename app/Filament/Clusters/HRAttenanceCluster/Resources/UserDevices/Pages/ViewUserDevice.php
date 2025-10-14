<?php

namespace App\Filament\Clusters\HRAttenanceCluster\Resources\UserDevices\Pages;

use App\Filament\Clusters\HRAttenanceCluster\Resources\UserDevices\UserDeviceResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewUserDevice extends ViewRecord
{
    protected static string $resource = UserDeviceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
