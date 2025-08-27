<?php

namespace App\Filament\Clusters\HRTasksSystem\Resources\DailyTasksSettingUpResource\Pages;

use Filament\Actions\DeleteAction;
use App\Filament\Clusters\HRTasksSystem\Resources\DailyTasksSettingUpResource;
use App\Filament\Clusters\HRTasksSystem\Resources\TaskResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Contracts\Support\Htmlable;

class EditDailyTasksSettingUp extends EditRecord
{
    protected static string $resource = DailyTasksSettingUpResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $recur_pattern = $this->record?->taskScheduleRequrrencePattern;
        $recur_pattern_details = json_decode($recur_pattern->recurrence_pattern);
        foreach ($recur_pattern_details as $key => $value) {
            $data[$key] = $value;
        }
// dd($data['requr_pattern_monthly_status']);
        // dd(TaskResource::getRequrPatternKeysAndValues($recur_pattern));
        $data['recur_count'] = $recur_pattern?->recur_count;
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

        return __('filament-panels::resources/pages/edit-record.title', [
            'label' => 'Scheduled task setup',
        ]);
    }

    protected function afterSave(): void
    {

        $this->record->taskScheduleRequrrencePattern()->update([
            'schedule_type' => $this->record->schedule_type,
            'start_date' => $this->record->start_date,
            'recur_count' => $this->data['recur_count'],
            'end_date' => $this->record->end_date,
            'recurrence_pattern' => json_encode(TaskResource::getRequrPatternKeysAndValues($this->data)),
        ]);
    }
}
