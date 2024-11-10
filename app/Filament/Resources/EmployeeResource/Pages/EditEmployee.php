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

    public function afterSave()
    {
        $employee = $this->record;
        // Access the related user model
        $user = $employee->user;

        if ($user) {

            // Check if 'email' or 'phone_number' changed
            // if ($employee->isDirty('email')) {
            $user->email = $employee->email;
            // }
            // if ($employee->isDirty('phone_number')) {
            $user->phone_number = $employee->phone_number;
            // }

            // if ($user->isDirty('name')) {
            $employee->name = $user->name;
            // }

            // if ($user->isDirty('branch_id')) {
            $employee->branch_id = $user->branch_id;
            // }

            if(!is_null($employee?->gender)){
                $employee->gender = $user->gender;
            }
            
            if(!is_null($employee?->nationality)){
                $employee->nationality = $user->nationality;
            }
            // Save changes to the user model
            $user->save();
        }
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
        $previousPeriods = $employee?->periods?->pluck('id')->toArray();
        $currentPeriods = $this?->data['periods'] ?? [];
        if (count($currentPeriods)) {

            // Determine added and removed periods
            $addedPeriods = array_diff($currentPeriods, $previousPeriods);
            $removedPeriods = array_diff($previousPeriods, $currentPeriods);

            // Log added periods
            if (!empty($addedPeriods)) {
                $employee->logPeriodChange($addedPeriods, Employee::TYPE_ACTION_EMPLOYEE_PERIOD_LOG_ADDED);
            }

            // Log removed periods
            if (!empty($removedPeriods)) {
                $employee->logPeriodChange($removedPeriods, Employee::TYPE_ACTION_EMPLOYEE_PERIOD_LOG_REMOVED);
            }
        }
    }

}
