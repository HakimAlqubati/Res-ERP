<?php

declare(strict_types=1);

namespace App\Filament\Central\Resources\Users\Pages;

use App\Filament\Central\Resources\Users\UserResource;
use Filament\Resources\Pages\CreateRecord;
use Spatie\Permission\Models\Role;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['active'] = 1;
        $data['role_id'] = 1;

        return $data;
    }

    protected function afterCreate(): void
    {
        $superAdminRole = Role::query()
            ->where('guard_name', 'web')
            ->where(function ($query): void {
                $query->where('id', 1)
                    ->orWhere('name', 'super_admin');
            })
            ->orderByRaw('CASE WHEN id = 1 THEN 0 ELSE 1 END')
            ->first();

        if ($superAdminRole) {
            $this->record->syncRoles([$superAdminRole]);
        }
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
