<?php

namespace App\Filament\Resources\EmployeeResource\Pages;

use App\Filament\Resources\EmployeeResource;
use App\Models\Employee;
use Filament\Pages\Actions;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\DB;

class CreateEmployee extends CreateRecord
{
    protected static string $resource = EmployeeResource::class;
    protected static bool $canCreateAnother = false;

    protected ?bool $hasDatabaseTransactions = true;
    
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['employee_no']= Employee::withTrashed()->latest()->first()?->id + 1;

        // Remove settings data from employee create — will be saved in afterCreate
        unset($data['settings']);

        return $data;
    }

    protected function afterCreate(): void
    {
        $settingsData = $this->data['settings'] ?? [];

        if (!empty($settingsData)) {
            $this->record->settings()->updateOrCreate(
                ['employee_id' => $this->record->id],
                $settingsData
            );
        }
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    
}
