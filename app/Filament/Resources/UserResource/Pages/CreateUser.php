<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use App\Models\Employee;
use Filament\Resources\Pages\CreateRecord;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;

    // protected function mutateFormDataBeforeSave(array $data): array
    // {
    //     dd($data);
    //     return $data;
    // }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        if ($data['is_exist_employee']) {
            $data['name'] = $this->data['name'];
            $data['email'] = $this->data['email'];
            $data['is_employee'] = 1;
            $data['owner_id'] = $this->data['owner_id'];
            $data['phone_number'] = $this->data['phone_number'];
            $data['branch_id'] = $this->data['branch_id'];
        }
        return $data;
    }

    protected function afterCreate(): void
    {
        if ($this->record->is_employee) {
            Employee::find($this->data['search_employee'])->update([
                'user_id' => $this->record->id,
            ]);
        }
    }
}
