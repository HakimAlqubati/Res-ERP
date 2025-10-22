<?php

namespace App\Filament\Clusters\SupplierStoresReportsCluster\Resources;

use Filament\Pages\Enums\SubNavigationPosition;
use App\Filament\Clusters\SupplierStoresReportsCluster\Resources\InventoryTransactionReportResource\Pages\ListManufacturingInventoryReport;
use App\Filament\Clusters\InventoryReportCluster;
use App\Filament\Clusters\SupplierStoresReportsCluster\Resources\InventoryTransactionReportResource\Pages;
use App\Models\Product;
use App\Models\Store;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class ManufacturingInventoryReportResource extends Resource
{
    protected static ?string $model = Product::class;
    protected static ?string $slug = 'manufacturing-inventory-report';
    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-chart-bar';
    protected static ?string $cluster = InventoryReportCluster::class;
    protected static ?\Filament\Pages\Enums\SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;
    protected static ?int $navigationSort = 3;

    public static function getLabel(): ?string
    {
        return 'Manufacturing Inventory';
    }
    public static function getNavigationLabel(): string
    {
        return 'Manufacturing Inventory';
    }
    public static function getPluralLabel(): ?string
    {
        return 'Manufacturing Inventory';
    }

    public static function table(Table $table): Table
    {
        return $table
        ->deferFilters(false)
        ->filters([
            SelectFilter::make('product_id')
                ->label('Product')
                ->searchable()
                ->placeholder('Choose Product')
                ->options(Product::active()->get()->mapWithKeys(fn($p) => [
                    $p->id => $p->code . ' - ' . $p->name
                ])),

            SelectFilter::make('store_id')
                ->label('Store')
                ->placeholder('Choose Store')
                ->searchable()
                ->options(Store::active()->where('is_central_kitchen', 1)
                    ->pluck('name', 'id')),

            Filter::make('options')
                ->label('Extra')
                ->schema([
                    Toggle::make('only_smallest_unit')
                        ->label('Only Smallest Unit')
                        ->default(false),
                ]),
        ], FiltersLayout::AboveContent);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListManufacturingInventoryReport::route('/'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return 'Report';
    }
    public static function canViewAny(): bool
    {
        if (isSuperAdmin()) {
            return true;
        }
        return false;
    }

    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }
}
