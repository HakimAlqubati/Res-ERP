<?php

namespace App\Filament\Clusters\HRTasksSystem\Resources\TaskStatusResource\Pages;

use App\Filament\Clusters\HRTasksSystem\Resources\TaskStatusResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateTaskStatus extends CreateRecord
{
    protected static string $resource = TaskStatusResource::class;
}
