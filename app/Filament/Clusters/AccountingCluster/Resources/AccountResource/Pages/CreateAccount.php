<?php

namespace App\Filament\Clusters\AccountingCluster\Resources\AccountResource\Pages;

use App\Filament\Clusters\AccountingCluster\Resources\AccountResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateAccount extends CreateRecord
{
    protected static string $resource = AccountResource::class;
}
