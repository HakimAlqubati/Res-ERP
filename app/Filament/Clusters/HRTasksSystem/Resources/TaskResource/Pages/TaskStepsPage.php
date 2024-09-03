<?php

namespace App\Filament\Clusters\HRTasksSystem\Resources\TaskResource\Pages;

use App\Filament\Clusters\HRTasksSystem\Resources\TaskResource;
use Filament\Resources\Pages\Page;

class TaskStepsPage extends Page
{
    protected static string $resource = TaskResource::class;

    protected static string $view = 'filament.clusters.h-r-tasks-system.resources.task-resource.pages.task-steps-page';
}
