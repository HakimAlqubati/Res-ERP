<?php

namespace App\Filament\Resources\FinancialTransactions\Pages;

use App\Filament\Resources\FinancialTransactions\FinancialTransactionResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\ViewRecord;

class ViewFinancialTransaction extends ViewRecord
{
    protected static string $resource = FinancialTransactionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->hidden(fn ($record) => $record->reference_type !== null)
                ->visible(fn () => FinancialTransactionResource::isUserSuperAdmin()),
        ];
    }
}
