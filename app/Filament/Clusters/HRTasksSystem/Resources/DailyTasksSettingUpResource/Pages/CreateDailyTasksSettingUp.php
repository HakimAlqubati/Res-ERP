<?php

namespace App\Filament\Clusters\HRTasksSystem\Resources\DailyTasksSettingUpResource\Pages;

use App\Filament\Clusters\HRTasksSystem\Resources\DailyTasksSettingUpResource;
use App\Filament\Clusters\HRTasksSystem\Resources\TaskResource;
use App\Models\Employee;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Contracts\Support\Htmlable;

class CreateDailyTasksSettingUp extends CreateRecord
{
    protected static string $resource = DailyTasksSettingUpResource::class;
    protected function mutateFormDataBeforeCreate(array $data): array
    {
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

    public function getTitle(): string | Htmlable
    {
        if (filled(static::$title)) {
            return static::$title;
        }

        return __('filament-panels::resources/pages/create-record.title', [
            'label' => 'Daily task setup',
        ]);
    }

    protected function afterCreate(): void
    {

        $this->record->taskScheduleRequrrencePattern()->create([
            'schedule_type' => $this->record->schedule_type,
            'start_date' => $this->record->start_date,
            'recur_count' => $this->data['recur_count'],
            'end_date' => $this->record->end_date,
            'recurrence_pattern' => json_encode(TaskResource::getRequrPatternKeysAndValues($this->data)),
        ]);

    }
}
