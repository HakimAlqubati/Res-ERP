<?php

namespace App\Filament\Clusters\SettingsCluster\Resources\NotificationSettingResource\Pages;

use Filament\Actions\CreateAction;
use App\Filament\Clusters\SettingsCluster\Resources\NotificationSettingResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListNotificationSettings extends ListRecords
{
    protected static string $resource = NotificationSettingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
