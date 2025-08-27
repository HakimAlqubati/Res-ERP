<?php

namespace App\Filament\Clusters\HRTasksSystem\Resources\TaskResource\Pages;

use Filament\Actions\CreateAction;
use Filament\Schemas\Components\Tabs\Tab;
use App\Filament\Clusters\HRTasksSystem\Resources\TaskResource;
use App\Models\Task;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;

class ListTasks extends ListRecords
{
    protected static string $resource = TaskResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }

    public function getTabs(): array
    {
        return [
            'All' => Tab::make()
                ->icon('heroicon-o-circle-stack') // Example icon
                ->badge(Task::count()) // Count of "Rejected" tasks
                ->modifyQueryUsing(fn(Builder $query) => $query),

            'New' => Tab::make()
                ->badgeColor(Task::COLOR_NEW)

                ->icon('heroicon-o-plus-circle') // Example icon for "New"
                ->badge(Task::where('task_status', Task::STATUS_NEW)->count()) // Count of "Rejected" tasks
                ->modifyQueryUsing(fn(Builder $query) => $query->where('task_status', Task::STATUS_NEW)),

            // 'Pending' => Tab::make()
            //     ->badgeColor(Task::COLOR_PENDING)
            //     ->icon('heroicon-o-clock') // Example icon for "Pending"
            //     ->badge(Task::where('task_status', Task::STATUS_PENDING)->count()) // Count of "Rejected" tasks
            //     ->modifyQueryUsing(fn(Builder $query) => $query->where('task_status', Task::STATUS_PENDING)),

            'In progress' => Tab::make()
                ->badgeColor(Task::COLOR_IN_PROGRESS)
                ->icon('heroicon-o-arrow-right-circle') // Example icon for "In Progress"
                ->badge(Task::where('task_status', Task::STATUS_IN_PROGRESS)->count()) // Count of "Rejected" tasks

                ->modifyQueryUsing(fn(Builder $query) => $query->where('task_status', Task::STATUS_IN_PROGRESS)),

            'Closed' => Tab::make()
                ->badgeColor(Task::COLOR_CLOSED)
                ->icon('heroicon-o-check-circle') // Example icon for "Closed"
                ->badge(Task::where('task_status', Task::STATUS_CLOSED)->count()) 
                ->modifyQueryUsing(fn(Builder $query) => $query->where('task_status', Task::STATUS_CLOSED)),
            'Rejected' => Tab::make()
                ->badgeColor(Task::COLOR_REJECTED)
                ->icon(Task::ICON_REJECTED) // Example icon for "Closed"
                ->badge(Task::where('task_status', Task::STATUS_REJECTED)->count()) // Count of "Rejected" tasks

                ->modifyQueryUsing(fn(Builder $query) => $query->where('task_status', Task::STATUS_REJECTED)),
        ];
    }
}
