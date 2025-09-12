<?php

namespace App\Filament\Resources\SalaryTransactions;

use App\Filament\Clusters\HRSalaryCluster;
use App\Filament\Resources\SalaryTransactions\Pages\CreateSalaryTransaction;
use App\Filament\Resources\SalaryTransactions\Pages\EditSalaryTransaction;
use App\Filament\Resources\SalaryTransactions\Pages\ListSalaryTransactions;
use App\Filament\Resources\SalaryTransactions\Pages\ViewSalaryTransaction;
use App\Filament\Resources\SalaryTransactions\Schemas\SalaryTransactionForm;
use App\Filament\Resources\SalaryTransactions\Schemas\SalaryTransactionInfolist;
use App\Filament\Resources\SalaryTransactions\Tables\SalaryTransactionsTable;
use App\Models\SalaryTransaction;
use BackedEnum;
use Filament\Pages\Enums\SubNavigationPosition;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class SalaryTransactionResource extends Resource
{
    protected static ?string $model = SalaryTransaction::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBanknotes;

    protected static ?string $recordTitleAttribute = 'employee.name';
    protected static ?string $cluster = HRSalaryCluster::class;
    protected static ?\Filament\Pages\Enums\SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;

    protected static ?int $navigationSort = 2;
    public static function form(Schema $schema): Schema
    {
        return SalaryTransactionForm::configure($schema);
    }
    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }

    public static function infolist(Schema $schema): Schema
    {
        return SalaryTransactionInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return SalaryTransactionsTable::configure($table);
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
            'index' => ListSalaryTransactions::route('/'),
            'create' => CreateSalaryTransaction::route('/create'),
            'view' => ViewSalaryTransaction::route('/{record}'),
            // 'edit' => EditSalaryTransaction::route('/{record}/edit'),
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
