<?php

namespace App\Filament\Clusters\HRTasksSystem\Resources\TaskResource\Pages;

use App\Filament\Clusters\HRTasksSystem\Resources\TaskResource;
use App\Models\DailyTasksSettingUp;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateTask extends CreateRecord
{
    protected static string $resource = TaskResource::class;
    protected function mutateFormDataBeforeCreate(array $data): array
    {

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
                'active' => 1,

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
