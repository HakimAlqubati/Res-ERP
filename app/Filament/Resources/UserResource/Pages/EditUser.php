<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use Filament\Pages\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Hash;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
            Actions\RestoreAction::make(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        if (is_null($this->data['password'])) {
            unset($data['password']);
        }
        return $data;
    }

    public function afterSave(): void
    {
        // Access the related employee model
        $user = $this->record;
        $employee = $user->employee;
        // dd($user,$employee);
        if ($employee) {

            // Check if 'email' or 'phone_number' has changed in the user model
            // if ($user->isDirty('email')) {
            $employee->email = $user->email;
            // }
            // if ($user->isDirty('phone_number')) {
            $employee->phone_number = $user->phone_number;
            // }
            // if ($user->isDirty('name')) {
            $employee->name = $user->name; 
            // }

            // if ($user->isDirty('branch_id')) {
            $employee->branch_id = $user->branch_id;

            if (!is_null($employee?->gender)) {
                $employee->gender = $user->gender;
            }

            if (!is_null($employee?->nationality)) {
                $employee->nationality = $user->nationality;
            }
            // Save changes to the employee model
            $employee->save();
        }

       
    }
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
