<?php

namespace App\Filament\Resources\EmployeePaymentMethods\Pages;

use App\Filament\Resources\EmployeePaymentMethods\EmployeePaymentMethodResource;
use Filament\Resources\Pages\CreateRecord;

class CreateEmployeePaymentMethod extends CreateRecord
{
    protected static string $resource = EmployeePaymentMethodResource::class;
}
