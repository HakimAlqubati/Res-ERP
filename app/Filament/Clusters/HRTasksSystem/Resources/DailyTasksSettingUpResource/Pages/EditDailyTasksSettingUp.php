<?php

namespace App\Filament\Clusters\HRTasksSystem\Resources\DailyTasksSettingUpResource\Pages;

use App\Filament\Clusters\HRTasksSystem\Resources\DailyTasksSettingUpResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditDailyTasksSettingUp extends EditRecord
{
    protected static string $resource = DailyTasksSettingUpResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
