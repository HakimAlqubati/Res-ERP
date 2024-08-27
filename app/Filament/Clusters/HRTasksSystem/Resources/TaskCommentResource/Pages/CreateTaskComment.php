<?php

namespace App\Filament\Clusters\HRTasksSystem\Resources\TaskCommentResource\Pages;

use App\Filament\Clusters\HRTasksSystem\Resources\TaskCommentResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateTaskComment extends CreateRecord
{
    protected static string $resource = TaskCommentResource::class;
}
