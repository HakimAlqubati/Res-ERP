<?php

namespace App\Filament\Clusters\SettingsCluster\Resources\NotificationSettingResource\Pages;

use App\Filament\Clusters\SettingsCluster\Resources\NotificationSettingResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateNotificationSetting extends CreateRecord
{
    protected static string $resource = NotificationSettingResource::class;
}
