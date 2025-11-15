<?php

namespace App\Filament\Clusters\POSIntegration\Resources\Products;

use App\Filament\Clusters\POSIntegration\POSIntegrationCluster;
use App\Filament\Clusters\POSIntegration\Resources\Products\Pages\CreateProduct;
use App\Filament\Clusters\POSIntegration\Resources\Products\Pages\EditProduct;
use App\Filament\Clusters\POSIntegration\Resources\Products\Pages\ListProducts;
use App\Filament\Clusters\POSIntegration\Resources\Products\Pages\ViewProduct;
use App\Filament\Clusters\POSIntegration\Resources\Products\Schemas\ProductForm;
use App\Filament\Clusters\POSIntegration\Resources\Products\Schemas\ProductInfolist;
use App\Filament\Clusters\POSIntegration\Resources\Products\Tables\ProductsTable;
use App\Models\Product;
use BackedEnum;
use Filament\Pages\Enums\SubNavigationPosition;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ProductResource extends Resource
{
    protected static ?string $model = Product::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::Cube;
    protected static ?\Filament\Pages\Enums\SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;
    protected static ?int $navigationSort = 3;
    protected static ?string $cluster = POSIntegrationCluster::class;

    protected static ?string $recordTitleAttribute = 'name';

    public static function getNavigationLabel(): string
    {
        return __('lang.menu_items');
    }

    public static function getLabel(): ?string
    {
        return __('lang.menu_items');
    }

    public static function getModelLabel(): string
    {
        return __('lang.menu_item');
    }

    public static function form(Schema $schema): Schema
    {
        return ProductForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return ProductInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ProductsTable::configure($table);
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
            'index' => ListProducts::route('/'),
            'create' => CreateProduct::route('/create'),
            'view' => ViewProduct::route('/{record}'),
            'edit' => EditProduct::route('/{record}/edit'),
        ];
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->pos()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
    public static function getNavigationBadge(): ?string
    {
        
        return static::getModel()::pos()->count();
    }
}
