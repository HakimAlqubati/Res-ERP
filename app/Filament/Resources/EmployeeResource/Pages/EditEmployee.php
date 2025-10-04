<?php

namespace App\Filament\Resources\EmployeeResource\Pages;

use Filament\Actions\DeleteAction;
use Filament\Actions\RestoreAction;
use App\Filament\Resources\EmployeeResource;
use App\Models\Employee;
use Filament\Pages\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Storage;

class EditEmployee extends EditRecord
{
    protected static string $resource = EmployeeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
            RestoreAction::make(),
        ];
    }

    // protected function getRedirectUrl(): string
    // {
    //     return $this->getResource()::getUrl('index');
    // }

    public function afterSave()
    {
        $employee = $this->record;
        // Access the related user model
        $user = $employee->user;

        // dd($user,$employee,!is_null($employee?->nationality), $employee->nationality);
        if ($user) {
            $managerUserId = Employee::find($employee->manager_id)?->user_id;
            $user->owner_id = $managerUserId;
            // Check if 'email' or 'phone_number' changed
            // if ($employee->isDirty('email')) {
            $user->email = $employee->email;
            // }
            // if ($employee->isDirty('phone_number')) {
            $user->phone_number = $employee?->phone_number;


            $user->name = $employee->name;
            $user->branch_id = $employee?->branch_id;


            $user->gender = $employee?->gender;

            if (!is_null($employee?->nationality)) {
                $user->nationality = $employee->nationality;
            }
            $user->user_type = $employee?->employee_type;

            if ($employee->avatar && Storage::disk('public')->exists($employee->avatar)) {
                $user->avatar = $employee->avatar;
            }
            // if ($employee->avatar && Storage::disk('s3')->exists($employee->avatar)) {
            //     $user->avatar = $employee->avatar;
            // }

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
