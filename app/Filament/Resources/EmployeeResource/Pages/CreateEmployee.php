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
 
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['employee_no']= (Employee::get()->last()->id) + 1;
        return $data;
    }


    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
