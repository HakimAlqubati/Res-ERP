<?php

namespace App\Filament\Clusters\HRTasksSystem\Resources\TaskResource\Pages;

use App\Filament\Clusters\HRTasksSystem\Resources\TaskResource;
use App\Models\Task;
use App\Models\TaskAttachment;
use App\Models\TaskLog;
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
            Action::make('MoveTask')
            ->hidden()
            ->form(function ($record) {
                return [
                    Select::make('task_status')->default(function ($record) {
                        return $record->task_status;
                    })->columnSpanFull()->options(
                        [
                            Task::STATUS_NEW => Task::STATUS_NEW,
                            Task::STATUS_PENDING => Task::STATUS_PENDING,
                            Task::STATUS_IN_PROGRESS => Task::STATUS_IN_PROGRESS,
                            Task::STATUS_CLOSED => Task::STATUS_CLOSED,
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

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
    protected function mutateFormDataBeforeSave(array $data): array
    {
        if (isset($data['file_path']) && is_array($data['file_path']) && count($data['file_path']) > 0) {
            foreach ($data['file_path'] as  $file) {
                TaskAttachment::create([
                    'task_id' => $this->record->id,
                    'file_name' => $file,
                    'file_path' => $file,
                    'created_by' => auth()->user()->id,
                    'updated_by' => auth()->user()->id,
                ]);
            }
        }

        if (isset($data['rating'])) {
           
        }
        return $data;
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $this->record->increment('views');
        if($this->record->task_status == Task::STATUS_NEW && $this->record->assigned_to == auth()->user()?->employee?->id){
            $currentStatus = Task::STATUS_NEW;
            $nextStatus = Task::STATUS_PENDING;
            $this->record->update(['task_status'=> Task::STATUS_PENDING]);
            $this->record->createLog(
                createdBy: auth()->id(), 
                description: "Task moved to {$nextStatus}", 
                logType: TaskLog::TYPE_MOVED, 
                details: [
                    'from' => $currentStatus, 
                    'to' => $nextStatus, 
                ]
            );
        }
        
        return $data;
    }
}
