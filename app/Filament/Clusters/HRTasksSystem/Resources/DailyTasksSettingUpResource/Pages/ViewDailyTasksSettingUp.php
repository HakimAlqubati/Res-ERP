<?php

namespace App\Filament\Clusters\HRTasksSystem\Resources\DailyTasksSettingUpResource\Pages;

use App\Filament\Clusters\HRTasksSystem\Resources\DailyTasksSettingUpResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewDailyTasksSettingUp extends ViewRecord
{
    protected static string $resource = DailyTasksSettingUpResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
