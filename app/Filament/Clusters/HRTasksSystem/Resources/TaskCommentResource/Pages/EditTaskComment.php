<?php

namespace App\Filament\Clusters\HRTasksSystem\Resources\TaskCommentResource\Pages;

use Filament\Actions\DeleteAction;
use App\Filament\Clusters\HRTasksSystem\Resources\TaskCommentResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditTaskComment extends EditRecord
{
    protected static string $resource = TaskCommentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
