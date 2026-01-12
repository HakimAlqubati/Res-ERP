<?php

namespace App\Filament\Clusters\FinanceFormattingCluster\Resources\Accounts;

use App\Models\Account;
use App\Filament\Clusters\FinanceFormattingCluster;
use App\Filament\Clusters\FinanceFormattingCluster\Resources\Accounts\Pages\CreateAccount;
use App\Filament\Clusters\FinanceFormattingCluster\Resources\Accounts\Pages\EditAccount;
use App\Filament\Clusters\FinanceFormattingCluster\Resources\Accounts\Pages\ListAccounts;
use App\Filament\Clusters\FinanceFormattingCluster\Resources\Accounts\Schemas\AccountForm;
use App\Filament\Clusters\FinanceFormattingCluster\Resources\Accounts\Tables\AccountsTable;
use BackedEnum;
use Filament\Pages\Enums\SubNavigationPosition;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class AccountResource extends Resource
{
    protected static ?string $model = Account::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBanknotes;

    // protected static ?string $cluster = FinanceFormattingCluster::class;
    // protected static ?\Filament\Pages\Enums\SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;
    // protected static ?int $navigationSort                         = 1;
    protected static ?string $recordTitleAttribute = 'account_name';

    public static function form(Schema $schema): Schema
    {
        return AccountForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return AccountsTable::configure($table);
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
            'index' => ListAccounts::route('/'),
            'create' => CreateAccount::route('/create'),
            'edit' => EditAccount::route('/{record}/edit'),
        ];
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }
}
