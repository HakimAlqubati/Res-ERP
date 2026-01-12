<?php

namespace App\Filament\Clusters\CashBoxAndBank\Resources\BankAccounts\Pages;

use App\Filament\Clusters\CashBoxAndBank\Resources\BankAccounts\BankAccountResource;
use Filament\Resources\Pages\CreateRecord;

class CreateBankAccount extends CreateRecord
{
    protected static string $resource = BankAccountResource::class;
}
