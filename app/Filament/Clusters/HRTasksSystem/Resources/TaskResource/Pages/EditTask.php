<?php

namespace App\Filament\Clusters\HRTasksSystem\Resources\TaskResource\Pages;

use App\Filament\Clusters\HRTasksSystem\Resources\TaskResource;
use App\Models\Task;
use Filament\Actions;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Resources\Pages\EditRecord;

class EditTask extends EditRecord
{
    protected static string $resource = TaskResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('MoveTask')->form(function ($record) {
                return [
                    Select::make('task_status')->default(function ($record) {
                        return $record->task_status;
                    })->columnSpanFull()->options(
                        [
                            Task::STATUS_PENDING => Task::STATUS_PENDING,
                            Task::STATUS_IN_PROGRESS => Task::STATUS_IN_PROGRESS,
                            Task::STATUS_REVIEW => Task::STATUS_REVIEW,
                            Task::STATUS_CANCELLED => Task::STATUS_CANCELLED,
                            Task::STATUS_FAILED => Task::STATUS_FAILED,
                            Task::STATUS_COMPLETED => Task::STATUS_COMPLETED,
                        ]
                    )
                ];
            })
                ->icon('heroicon-m-arrows-right-left')
                ->action(function (array $data, $record): void {
                    $record->update([
                        'task_status' => $data['task_status']
                    ]);
                }),
            // Actions\DeleteAction::make(),
        ];
    }
}
