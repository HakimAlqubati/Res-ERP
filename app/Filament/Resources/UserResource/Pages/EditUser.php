<?php

namespace App\Filament\Resources\UserResource\Pages;

use Filament\Actions\DeleteAction;
use Filament\Actions\RestoreAction;
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
            DeleteAction::make(),
            RestoreAction::make(),
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

        if (! $employee) {
            return;
        }

        $updates = [];

        if ($user->wasChanged('email')) {
            $updates['email'] = $user->email;
        }
        if ($user->wasChanged('phone_number')) {
            $updates['phone_number'] = $user->phone_number;
        }
        if ($user->wasChanged('name')) {
            $updates['name'] = $user->name;
        }

        $updates['employee_type'] = $user->user_type;

        if ($user->wasChanged('branch_id')) {
            $updates['branch_id'] = $user->branch_id;

            // 🟢 لا نحذف المدير إلا إذا لم يتم تحديد Owner جديد
            if (! $user->wasChanged('owner_id')) {
                $updates['manager_id'] = null;
            }
        }

        if ($user->wasChanged('owner_id')) {
            if ($user->owner_id) {
                $managerEmployee = \App\Models\User::find($user->owner_id)?->employee;
                if ($managerEmployee) {
                    $updates['manager_id'] = $managerEmployee->id;
                } else {
                    // 🟢 المدير الجديد لا يملك Employee → تصفير
                    $updates['manager_id'] = null;
                }
            } else {
                // 🟢 في حال تم إزالة الـ owner_id تماماً
                $updates['manager_id'] = null;
            }
        }


        if ($user->wasChanged('gender')) {
            $updates['gender'] = $user->gender;
        }
        if ($user->wasChanged('nationality')) {
            $updates['nationality'] = $user->nationality;
        }



        // dd($updates);

        if (! empty($updates)) {
            $employee->updateQuietly($updates);
        }
    }
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
