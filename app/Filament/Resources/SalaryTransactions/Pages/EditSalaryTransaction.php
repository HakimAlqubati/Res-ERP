<?php

namespace App\Filament\Resources\SalaryTransactions\Pages;

use App\Filament\Resources\SalaryTransactions\SalaryTransactionResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditSalaryTransaction extends EditRecord
{
    protected static string $resource = SalaryTransactionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make()->visible(fn() => SalaryTransactionResource::isUserSuperAdmin()),
            RestoreAction::make()->visible(fn() => SalaryTransactionResource::isUserSuperAdmin()),
        ];
    }
    
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
