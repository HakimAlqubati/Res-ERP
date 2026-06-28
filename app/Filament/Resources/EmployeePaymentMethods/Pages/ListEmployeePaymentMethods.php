<?php

namespace App\Filament\Resources\EmployeePaymentMethods\Pages;

use App\Filament\Resources\EmployeePaymentMethods\EmployeePaymentMethodResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListEmployeePaymentMethods extends ListRecords
{
    protected static string $resource = EmployeePaymentMethodResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
