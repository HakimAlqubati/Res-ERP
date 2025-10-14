<?php

namespace App\Filament\Clusters\HRAttenanceCluster\Resources\UserDevices\Pages;

use App\Filament\Clusters\HRAttenanceCluster\Resources\UserDevices\UserDeviceResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditUserDevice extends EditRecord
{
    protected static string $resource = UserDeviceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
