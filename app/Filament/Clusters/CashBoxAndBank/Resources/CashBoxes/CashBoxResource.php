<?php

namespace App\Filament\Clusters\CashBoxAndBank\Resources\CashBoxes;

use App\Filament\Clusters\CashBoxAndBank\CashBoxAndBankCluster;
use App\Filament\Clusters\CashBoxAndBank\Resources\CashBoxes\Pages\CreateCashBox;
use App\Filament\Clusters\CashBoxAndBank\Resources\CashBoxes\Pages\EditCashBox;
use App\Filament\Clusters\CashBoxAndBank\Resources\CashBoxes\Pages\ListCashBoxes;
use App\Filament\Clusters\CashBoxAndBank\Resources\CashBoxes\Schemas\CashBoxForm;
use App\Filament\Clusters\CashBoxAndBank\Resources\CashBoxes\Tables\CashBoxesTable;
use App\Models\CashBox;
use BackedEnum;
use Filament\Pages\Enums\SubNavigationPosition;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class CashBoxResource extends Resource
{
    protected static ?string $model = CashBox::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCurrencyDollar;

    protected static ?string $cluster = CashBoxAndBankCluster::class;

    protected static ?string $recordTitleAttribute = 'name';
    protected static ?\Filament\Pages\Enums\SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;
    protected static ?int $navigationSort                         = 1;

    public static function form(Schema $schema): Schema
    {
        return CashBoxForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CashBoxesTable::configure($table);
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
            'index' => ListCashBoxes::route('/'),
            'create' => CreateCashBox::route('/create'),
            'edit' => EditCashBox::route('/{record}/edit'),
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
