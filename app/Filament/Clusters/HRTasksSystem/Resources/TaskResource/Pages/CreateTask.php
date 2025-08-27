<?php

namespace App\Filament\Clusters\HRTasksSystem\Resources\TaskResource\Pages;

use App\Models\Task;
use Exception;
use Filament\Notifications\Notification;
use App\Filament\Clusters\HRTasksSystem\Resources\TaskResource;
use App\Models\DailyTasksSettingUp;
use App\Models\Employee;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\DB;

class CreateTask extends CreateRecord
{
    protected static string $resource = TaskResource::class;

    public function create(bool $another = false): void
    {
        $data = $this->form->getState(); // Get form data
        DB::beginTransaction(); // Start database transaction
       
        try {
            $steps = $this->form->getLivewire()->data['steps'];
            // Loop through each assigned employee and create a task
            foreach ($data['assigned_to_multi'] as $assignedTo) {
                $taskData = $data;
                $taskData['assigned_to'] = $assignedTo; // Assign to specific employee
                $taskData['created_by'] = auth()->user()->id;

                // Ensure branch_id is set if the employee belongs to a branch
                $employee = Employee::find($assignedTo);
                if ($employee && $employee->branch()->exists()) {
                    $taskData['branch_id'] = $employee->branch->id;
                }

                // Create the task
                $task = Task::create($taskData);
                foreach ($steps as $step) {
                    $task->steps()->create([
                        'title' => $step['title'],
                    ]);
                }
                // // If the task is a scheduled task, handle its setup
                if ($task->is_daily == 1) {
                    $this->handleDailyTaskSetup($task);
                }
            }

            DB::commit(); // Commit transaction

            // Redirect to the task list page
            $this->redirect($this->getRedirectUrl());
        } catch (Exception $e) {
            DB::rollBack(); // Rollback transaction in case of failure
            Notification::make()
                ->title('Error')
                ->body('Task creation failed: ' . $e->getMessage())
                ->danger()
                ->send();
        }
    }
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['created_by'] = auth()->user()->id;
        $employee = Employee::find($data['assigned_to']);
        if ($employee->branch()->exists()) {
            $data['branch_id'] = $employee->branch->id;
        }
        return $data;
    }

    /**
     * Handles setup for daily scheduled tasks.
     */
    private function handleDailyTaskSetup($task)
    {
        $dailyTask = DailyTasksSettingUp::create([
            'title'        => $task->title,
            'schedule_type' => $task->schedule_type,
            'description'  => $task->description,
            'updated_by'   => $task->updated_by,
            'created_by'   => $task->created_by,
            'assigned_to'  => $task->assigned_to,
            'assigned_by'  => $task->assigned_by,
            'start_date'   => $task->start_date,
            'end_date'     => $task->end_date,
            'branch_id'    => $task->branch_id ?? null,
            'active'       => 1,
        ]);

        $dailyTask->taskScheduleRequrrencePattern()->create([
            'schedule_type' => $task->schedule_type,
            'start_date'    => $task->start_date,
            'recur_count'   => $this->data['recur_count'],
            'end_date'      => $task->end_date,
            'recurrence_pattern' => json_encode(TaskResource::getRequrPatternKeysAndValues($this->data)),
        ]);

        // Copy steps from the original task
        foreach ($task->steps as $step) {
            $dailyTask->steps()->create([
                'title' => $step->title,
                'order' => $step->order,
            ]);
        }
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function afterCreate(): void
    {
        if ($this->record->is_daily == 1) {
            $dailyTask = DailyTasksSettingUp::create([
                'title' => $this->record->title,
                'schedule_type' => $this?->record?->schedule_type,
                'description' => $this->record->description,
                'updated_by' => $this->record->updated_by,
                'created_by' => $this->record->created_by,
                'assigned_to' => $this->record->assigned_to,
                'assigned_by' => $this->record->assigned_by,
                'start_date' => $this->record->start_date,
                'end_date' => $this->record->end_date,
                'branch_id' => !is_null($this?->record?->branch_id) ? $this->record->branch_id : null,
                'active' => 1,

            ]);

            $dailyTask->taskScheduleRequrrencePattern()->create([
                'schedule_type' => $this->record->schedule_type,
                'start_date' => $this->record->start_date,
                'recur_count' => $this->data['recur_count'],
                'end_date' => $this->record->end_date,
                'recurrence_pattern' => json_encode(TaskResource::getRequrPatternKeysAndValues($this->data)),
            ]);
            foreach ($this->record->steps as $step) {
                $dailyTask->steps()->create([
                    'title' => $step->title,
                    'order' => $step->order,
                ]);
            }
        }

        //    dd($this->data,$this->record);
    }
}
