<?php

namespace App\Filament\Clusters\HRTasksSystem\Resources\TaskStatusResource\Pages;

use Filament\Actions\DeleteAction;
use App\Filament\Clusters\HRTasksSystem\Resources\TaskStatusResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditTaskStatus extends EditRecord
{
    protected static string $resource = TaskStatusResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
