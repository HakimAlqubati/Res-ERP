<?php

namespace App\Filament\Resources\FinancialTransactions\Pages;

use App\Filament\Resources\FinancialTransactions\FinancialTransactionResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Pages\EditRecord;

class EditFinancialTransaction extends EditRecord
{
    protected static string $resource = FinancialTransactionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->hidden(fn ($record) => $record->reference_type !== null)
                ->visible(fn () => FinancialTransactionResource::isUserSuperAdmin()),
            RestoreAction::make()
                ->visible(fn () => FinancialTransactionResource::isUserSuperAdmin()),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
