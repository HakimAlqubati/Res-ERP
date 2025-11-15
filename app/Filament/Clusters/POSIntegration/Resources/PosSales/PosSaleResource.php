<?php

namespace App\Filament\Clusters\POSIntegration\Resources\PosSales;

use App\Filament\Clusters\POSIntegration\POSIntegrationCluster;
use App\Filament\Clusters\POSIntegration\Resources\PosSales\Pages\CreatePosSale;
use App\Filament\Clusters\POSIntegration\Resources\PosSales\Pages\EditPosSale;
use App\Filament\Clusters\POSIntegration\Resources\PosSales\Pages\ListPosSales;
use App\Filament\Clusters\POSIntegration\Resources\PosSales\Pages\ViewPosSale;
use App\Filament\Clusters\POSIntegration\Resources\PosSales\RelationManagers\ItemsRelationManager;
use App\Filament\Clusters\POSIntegration\Resources\PosSales\Schemas\PosSaleForm;
use App\Filament\Clusters\POSIntegration\Resources\PosSales\Schemas\PosSaleInfolist;
use App\Filament\Clusters\POSIntegration\Resources\PosSales\Tables\PosSalesTable;
use App\Models\PosSale;
use BackedEnum;
use Filament\Pages\Enums\SubNavigationPosition;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class PosSaleResource extends Resource
{
    protected static ?string $model = PosSale::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::ShoppingCart;

    protected static ?string $cluster = POSIntegrationCluster::class;
    protected static ?\Filament\Pages\Enums\SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;
    protected static ?int $navigationSort = 4;
    protected static ?string $recordTitleAttribute = 'id';

    public static function form(Schema $schema): Schema
    {
        return PosSaleForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return PosSaleInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PosSalesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            ItemsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPosSales::route('/'),
            'create' => CreatePosSale::route('/create'),
            'view' => ViewPosSale::route('/{record}'),
            // 'edit' => EditPosSale::route('/{record}/edit'),
        ];
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()
            // ->withoutGlobalScopes([
            //     SoftDeletingScope::class,
            // ])
            ;
    }
    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }
}
