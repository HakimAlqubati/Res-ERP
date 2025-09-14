<?php

namespace App\Filament\Resources\SalaryTransactions\Pages;

use App\Filament\Resources\SalaryTransactions\SalaryTransactionResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListSalaryTransactions extends ListRecords
{
    protected static string $resource = SalaryTransactionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
