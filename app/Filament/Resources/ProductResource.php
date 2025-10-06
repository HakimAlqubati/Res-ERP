<?php

namespace App\Filament\Resources;

use Filament\Pages\Enums\SubNavigationPosition;
use Filament\Schemas\Schema;
use App\Filament\Resources\ProductResource\Pages\ManageProducts;
use App\Filament\Resources\ProductResource\Pages\CreateProduct;
use App\Filament\Resources\ProductResource\Pages\EditProduct;
use App\Filament\Resources\ProductResource\Pages\ViewProduct;
use App\Filament\Resources\ProductResource\RelationManagers\ProductPriceHistoriesRelationManager;
use App\Filament\Clusters\ProductUnitCluster;
use App\Filament\Resources\ProductResource\Schema\ProductsSchema;
use App\Filament\Resources\ProductResource\Tables\ProductsTable;
use App\Models\Product;
use Filament\Pages\Page;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;

// use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;

class ProductResource extends Resource
{
    protected static ?string $model                               = Product::class;
    protected static ?string $cluster                             = ProductUnitCluster::class;
    protected static string | \BackedEnum | null $navigationIcon                      = Heroicon::Cube;
    protected static ?string $recordTitleAttribute                = 'name';
    protected static ?\Filament\Pages\Enums\SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;
    protected static ?int $navigationSort                         = 1;
    // protected static ?string $navigationGroup = 'Products - units';

    public static function getPluralLabel(): ?string
    {
        return __('lang.products');
    }
    public static function getNavigationLabel(): string
    {
        return __('lang.products');
    }

    // public static function getRecordTitleAttribute(): ?string
    // {
    //     return __('lang.products');
    // }

    public static function form(Schema $schema): Schema
    {
        return ProductsSchema::configure($schema);
    }

    public static function table(Table $table): Table
    {

        return ProductsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index'  => ManageProducts::route('/'),
            'create' => CreateProduct::route('/create'),
            'edit'   => EditProduct::route('/{record}/edit'),
            'view'   => ViewProduct::route('/{record}'),
        ];
    }

    public static function getRecordSubNavigation(Page $page): array
    {
        return $page->generateNavigationItems([
            ManageProducts::class,
            CreateProduct::class,
            EditProduct::class,
            ViewProduct::class,
            // Pages\ViewEmployee::class,
        ]);
    }

    public static function getRelations(): array
    {
        return [
            // RelationManagers\UnitPricesRelationManager::class,
            ProductPriceHistoriesRelationManager::class,
            // RelationManagers\FinalProductCostingHistoriesRelationManager::class,

        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }
    public static function getGlobalSearchResultTitle(Model $record): string
    {
        return $record->code.' - '.$record->name;
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['name', 'code'];
    }


    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
        // $query->withMinimumUnitPrices();
        return $query;
    }



    public static function canEdit(Model $record): bool
    {
        if (isSuperAdmin() || isSystemManager() || isFinanceManager()) {
            return true;
        }
        return false;
    }

    
    public static function getGlobalSearchResultsLimit(): int
    {
        return 20;
    }
}
