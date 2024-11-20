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
        return $data;
    }


    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    
}
