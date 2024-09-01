<?php

namespace App\Filament\Clusters\HRTasksSystem\Resources\TasksMenuResource\Pages;

use App\Filament\Clusters\HRTasksSystem\Resources\TasksMenuResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListTasksMenus extends ListRecords
{
    protected static string $resource = TasksMenuResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
