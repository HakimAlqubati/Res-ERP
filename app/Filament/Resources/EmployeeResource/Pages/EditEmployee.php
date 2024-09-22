<?php

namespace App\Filament\Resources\EmployeeResource\Pages;

use App\Filament\Resources\EmployeeResource;
use App\Models\Employee;
use Filament\Pages\Actions;
use Filament\Resources\Pages\EditRecord;

class EditEmployee extends EditRecord
{
    protected static string $resource = EmployeeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
            Actions\RestoreAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // dd($data['employee_periods'],$this->record->id);
        $this->logPeriodChanges();
        return $data;
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        return $data;
    }

    protected function logPeriodChanges()
    {
        // Get the employee being edited
        $employee = Employee::find($this->record->id);

        // Get previous and current period IDs
        $previousPeriods = $employee->periods->pluck('id')->toArray();
        $currentPeriods = $this->data['periods'] ?? [];

        // Determine added and removed periods
        $addedPeriods = array_diff($currentPeriods, $previousPeriods);
        $removedPeriods = array_diff($previousPeriods, $currentPeriods);
        // dd($addedPeriods, $removedPeriods);
        // Log added periods
        // foreach ($addedPeriods as $period_id) {
        //     $employee->periods()->create(['period_id', $period_id]);
        // }
        if (!empty($addedPeriods)) {
            $employee->logPeriodChange($addedPeriods, Employee::TYPE_ACTION_EMPLOYEE_PERIOD_LOG_ADDED);
        }

        // $employee->periods()->whereIn('period_id',$removedPeriods)->delete();
        // Log removed periods
        if (!empty($removedPeriods)) {
            $employee->logPeriodChange($removedPeriods, Employee::TYPE_ACTION_EMPLOYEE_PERIOD_LOG_REMOVED);
        }
    }
}
