<?php

namespace App\Filament\Clusters\FinanceFormattingCluster\Resources\Accounts\Pages;

use App\Filament\Clusters\FinanceFormattingCluster\Resources\Accounts\AccountResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Icons\Heroicon;

class ListAccounts extends ListRecords
{
    protected static string $resource = AccountResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->icon(Heroicon::OutlinedPlusCircle),
        ];
    }
}
