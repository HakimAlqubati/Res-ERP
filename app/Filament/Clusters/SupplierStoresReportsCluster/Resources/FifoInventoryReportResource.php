<?php

namespace App\Filament\Clusters\SupplierStoresReportsCluster\Resources;

use App\Filament\Clusters\InventoryReportCluster;
use App\Filament\Clusters\SupplierStoresReportsCluster;
use App\Filament\Clusters\SupplierStoresReportsCluster\Resources\InventoryTransactionReportResource\Pages;
use App\Models\Category;
use App\Models\Product;
use App\Models\Store;
use Filament\Forms\Components\Toggle;
use Filament\Pages\SubNavigationPosition;
use Filament\Resources\Resource;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class FifoInventoryReportResource extends Resource
{
    protected static ?string $model = Product::class;
    protected static ?string $slug = 'fifo-inventory-report';
    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static ?string $cluster = InventoryReportCluster::class;
    protected static SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;
    protected static ?int $navigationSort = 2;
    public static function getLabel(): ?string
    {
        return 'FIFO Inventory';
    }

    public static function getNavigationLabel(): string
    {
        return 'FIFO Inventory';
    }

    public static function getPluralLabel(): ?string
    {
        return 'FIFO Inventorys';
    }

    public static function table(Table $table): Table
    {
        return $table->filters([


            SelectFilter::make('product_id')
                ->label('Product')
                ->searchable()
                ->options(Product::active()->get()->mapWithKeys(fn($p) => [
                    $p->id => $p->code . ' - ' . $p->name
                ])),

            SelectFilter::make('store_id')
                ->label('Store')
                ->searchable()
                ->options(Store::active()->pluck('name', 'id')),

            Filter::make('options')
                ->label('Extra')
                ->form([
                    Toggle::make('only_smallest_unit')
                        ->label('Only Smallest Unit')
                        ->default(false),
                ]),
        ], FiltersLayout::AboveContent);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListFifoInventoryReport::route('/'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return 'Report';
    }
}
