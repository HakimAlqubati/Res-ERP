<?php

namespace App\Filament\Clusters\HRTasksSystem\Resources\DailyTasksSettingUpResource\Pages;

use App\Filament\Clusters\HRTasksSystem\Resources\DailyTasksSettingUpResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateDailyTasksSettingUp extends CreateRecord
{
    protected static string $resource = DailyTasksSettingUpResource::class;
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['assigned_to_users'] = json_encode($data['assigned_to_users']);
        
        return $data;
    }
}
