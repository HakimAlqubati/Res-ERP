<?php

namespace App\Filament\Clusters\HRTasksSystem\Resources\DailyTasksSettingUpResource\Pages;

use Filament\Actions\CreateAction;
use App\Filament\Clusters\HRTasksSystem\Resources\DailyTasksSettingUpResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListDailyTasksSettingUps extends ListRecords
{
    protected static string $resource = DailyTasksSettingUpResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }

    public function getModelLabel(): ?string
    {
        return 'Scheduled Task';
    }
}
