<?php

namespace App\Filament\Clusters\SettingsCluster\Resources\NotificationSettingResource\Pages;

use Filament\Actions\DeleteAction;
use App\Filament\Clusters\SettingsCluster\Resources\NotificationSettingResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditNotificationSetting extends EditRecord
{
    protected static string $resource = NotificationSettingResource::class;

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
