<?php

namespace App\Filament\Clusters\POSIntegration\Resources\Categories;

use App\Filament\Clusters\POSIntegration\POSIntegrationCluster;
use App\Filament\Clusters\POSIntegration\Resources\Categories\Pages\CreateCategory;
use App\Filament\Clusters\POSIntegration\Resources\Categories\Pages\EditCategory;
use App\Filament\Clusters\POSIntegration\Resources\Categories\Pages\ListCategories;
use App\Filament\Clusters\POSIntegration\Resources\Categories\Schemas\CategoryForm;
use App\Filament\Clusters\POSIntegration\Resources\Categories\Tables\CategoriesTable;
use App\Models\Category;
use BackedEnum;
use Filament\Pages\Enums\SubNavigationPosition;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class CategoryResource extends Resource
{
    protected static ?string $model = Category::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::Squares2x2;
    protected static ?\Filament\Pages\Enums\SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;
    protected static ?int $navigationSort = 2;
    protected static ?string $cluster = POSIntegrationCluster::class;

    protected static ?string $recordTitleAttribute = 'name';

    public static function getNavigationLabel(): string
    {
        return __('lang.menu_categories');
    }

    public static function getLabel(): ?string
    {
        return __('lang.menu_categories');
    }

    public static function getModelLabel(): string
    {
        return __('lang.menu_category');
    }


    public static function form(Schema $schema): Schema
    {
        return CategoryForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CategoriesTable::configure($table);
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
            'index' => ListCategories::route('/'),
            'create' => CreateCategory::route('/create'),
            'edit' => EditCategory::route('/{record}/edit'),
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
            ->forPos()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
        public static function getNavigationBadge(): ?string
    {
        return static::getModel()::forPos()->count();
    }
}
