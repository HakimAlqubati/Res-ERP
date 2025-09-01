<?php

namespace App\Filament\Clusters\HRTasksSystem\Resources\TasksMenuResource\Pages;

use Filament\Actions\DeleteAction;
use App\Filament\Clusters\HRTasksSystem\Resources\TasksMenuResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditTasksMenu extends EditRecord
{
    protected static string $resource = TasksMenuResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
