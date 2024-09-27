<?php

namespace App\Filament\Clusters\HRTasksSystem\Resources\TaskResource\Pages;

use App\Filament\Clusters\HRTasksSystem\Resources\TaskResource;
use App\Models\DailyTasksSettingUp;
use App\Models\Employee;
use Filament\Resources\Pages\CreateRecord;

class CreateTask extends CreateRecord
{
    protected static string $resource = TaskResource::class;
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['created_by'] = auth()->user()->id;
        $employee = Employee::find($data['assigned_to']);
        if ($employee->branch()->exists()) {
            $data['branch_id'] = $employee->branch->id;
        }
        return $data;
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
