<?php

namespace App\Filament\Clusters\HRTasksSystem\Resources\TaskResource\Pages;

use App\Filament\Clusters\HRTasksSystem\Resources\TaskResource;
use App\Models\Task;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Tables\Table;
use Filament\Resources\Components\Tab;
use Illuminate\Database\Eloquent\Builder;
class ListTasks extends ListRecords
{
    protected static string $resource = TaskResource::class;


    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    public function getTabs(): array
    {
        return [
            // 'All' => Tab::make()
            //     ->modifyQueryUsing(fn(Builder $query) => $query),
            'New' => Tab::make()
                ->modifyQueryUsing(fn(Builder $query) => $query->where('task_status', Task::STATUS_NEW))
                ,
            'Pending' => Tab::make()
                ->modifyQueryUsing(fn(Builder $query) => $query->where('task_status', Task::STATUS_PENDING))
                ,
            'In progress' => Tab::make()
                ->modifyQueryUsing(fn(Builder $query) => $query->where('task_status', Task::STATUS_IN_PROGRESS))
                ,
            'Closed' => Tab::make()
                ->modifyQueryUsing(fn(Builder $query) => $query->where('task_status', Task::STATUS_CLOSED))
                ,
        ];
    }
}
