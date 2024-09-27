<?php

namespace App\Filament\Clusters\HRTasksSystem\Resources\DailyTasksSettingUpResource\Pages;

use App\Filament\Clusters\HRTasksSystem\Resources\DailyTasksSettingUpResource;
use App\Models\Employee;
use Filament\Actions;
use Filament\Actions\Action;
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
}
