<?php

namespace App\Filament\Clusters\CashBoxAndBank\Resources\BankAccounts;

use App\Filament\Clusters\CashBoxAndBank\CashBoxAndBankCluster;
use App\Filament\Clusters\CashBoxAndBank\Resources\BankAccounts\Pages\CreateBankAccount;
use App\Filament\Clusters\CashBoxAndBank\Resources\BankAccounts\Pages\EditBankAccount;
use App\Filament\Clusters\CashBoxAndBank\Resources\BankAccounts\Pages\ListBankAccounts;
use App\Filament\Clusters\CashBoxAndBank\Resources\BankAccounts\Schemas\BankAccountForm;
use App\Filament\Clusters\CashBoxAndBank\Resources\BankAccounts\Tables\BankAccountsTable;
use App\Models\BankAccount;
use BackedEnum;
use Filament\Pages\Enums\SubNavigationPosition;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class BankAccountResource extends Resource
{
    protected static ?string $model = BankAccount::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBanknotes;

    protected static ?string $cluster = CashBoxAndBankCluster::class;
    protected static ?\Filament\Pages\Enums\SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;
    protected static ?int $navigationSort                         = 1;

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return BankAccountForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return BankAccountsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListBankAccounts::route('/'),
            'create' => CreateBankAccount::route('/create'),
            'edit' => EditBankAccount::route('/{record}/edit'),
        ];
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
