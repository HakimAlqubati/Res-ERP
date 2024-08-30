<?php

namespace App\Filament\Clusters\HRTasksSystem\Resources\TaskResource\Pages;

use App\Filament\Clusters\HRTasksSystem\Resources\TaskResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateTask extends CreateRecord
{
    protected static string $resource = TaskResource::class;
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        
        return $data;
    }
}
