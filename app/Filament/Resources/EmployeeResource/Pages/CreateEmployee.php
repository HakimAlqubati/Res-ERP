<?php

namespace App\Filament\Resources\EmployeeResource\Pages;

use App\Filament\Resources\EmployeeResource;
use Filament\Pages\Actions;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\DB;

class CreateEmployee extends CreateRecord
{
    protected static string $resource = EmployeeResource::class;

    

    // protected function mutateFormDataBeforeSave(array $data): array
    // {
    //     dd($data);
    //     return $data;
    // }

    // protected function getRedirectUrl(): string
    // {
    //     return 'filament.admin.h-r.resources.employees.index';
    // }

}
