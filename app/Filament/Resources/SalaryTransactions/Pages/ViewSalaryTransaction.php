<?php

namespace App\Filament\Resources\SalaryTransactions\Pages;

use App\Filament\Resources\SalaryTransactions\SalaryTransactionResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewSalaryTransaction extends ViewRecord
{
    protected static string $resource = SalaryTransactionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
