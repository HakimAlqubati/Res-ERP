<?php

namespace App\Filament\Resources\SalaryTransactions\Pages;

use App\Filament\Resources\SalaryTransactions\SalaryTransactionResource;
use Filament\Resources\Pages\CreateRecord;

class CreateSalaryTransaction extends CreateRecord
{
    protected static string $resource = SalaryTransactionResource::class;
    
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
