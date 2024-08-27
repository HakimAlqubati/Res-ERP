<?php

namespace App\Filament\Clusters\HRTasksSystem\Resources\TaskCommentResource\Pages;

use App\Filament\Clusters\HRTasksSystem\Resources\TaskCommentResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListTaskComments extends ListRecords
{
    protected static string $resource = TaskCommentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
