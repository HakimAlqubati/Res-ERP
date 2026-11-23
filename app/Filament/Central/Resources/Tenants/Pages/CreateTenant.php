<?php

namespace App\Filament\Central\Resources\Tenants\Pages;

use App\Filament\Central\Resources\Tenants\TenantResource;
use App\Models\CustomTenantModel;
use Filament\Resources\Pages\CreateRecord;

class CreateTenant extends CreateRecord
{
    protected static string $resource = TenantResource::class;

    protected ?bool $hasDatabaseTransactions = false;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['domain'] = $data['domain'] . '.' . config('app.domain');

        return $data;
    }

    protected function afterCreate(): void
    {
        (new CustomTenantModel())->importDatabase($this->record);
    }
}
