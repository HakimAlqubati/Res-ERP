<?php

namespace App\Filament\Clusters\FinanceFormattingCluster\Resources\Accounts\Pages;

use App\Filament\Clusters\FinanceFormattingCluster\Resources\Accounts\AccountResource;
use Filament\Resources\Pages\CreateRecord;

class CreateAccount extends CreateRecord
{
    protected static string $resource = AccountResource::class;
}
