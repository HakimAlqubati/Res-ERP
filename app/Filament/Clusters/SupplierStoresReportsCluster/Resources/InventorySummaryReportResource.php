<?php

namespace App\Filament\Clusters\SupplierStoresReportsCluster\Resources;

use Filament\Pages\Enums\SubNavigationPosition;
use App\Filament\Clusters\SupplierStoresReportsCluster\Resources\InventorySummaryReportResource\Pages\ListInventorySummaryReport;
use App\Filament\Clusters\InventoryReportCluster;
use App\Models\Category;
use App\Models\InventorySummary;
use App\Models\Product;
use App\Models\Store;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class InventorySummaryReportResource extends Resource
{
    protected static ?string $model = InventorySummary::class;
    protected static ?string $slug = 'inventory-summary-report';
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-chart-bar';

    public static function getLabel(): ?string
    {
        return 'Inventory Summary';
    }

    public static function getNavigationLabel(): string
    {
        return 'Inventory Summary';
    }

    public static function getPluralLabel(): ?string
    {
        return 'Inventory Summary';
    }

    protected static ?string $cluster = InventoryReportCluster::class;
    protected static ?\Filament\Pages\Enums\SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;
    protected static ?int $navigationSort = 2;

    public static function table(Table $table): Table
    {
        return $table->deferFilters(false)
            ->filters([
                SelectFilter::make("category_id")
                    ->label(__('lang.category'))
                    ->searchable()
                    ->query(fn(Builder $q, $data) => $q)
                    ->options(Category::active()->get()->pluck('name', 'id')),

                SelectFilter::make("product_id")
                    ->label(__('lang.product'))
                    ->searchable()
                    ->query(fn(Builder $q, $data) => $q)
                    ->getSearchResultsUsing(function (string $search): array {
                        return Product::query()
                            ->where(function ($query) use ($search) {
                                $query->where('name', 'like', "%{$search}%")
                                    ->orWhere('code', 'like', "%{$search}%");
                            })
                            ->limit(10)
                            ->get()
                            ->mapWithKeys(fn($product) => [
                                $product->id => "{$product->code} - {$product->name}"
                            ])
                            ->toArray();
                    })
                    ->getOptionLabelUsing(function ($value): ?string {
                        $product = Product::find($value);
                        return $product ? "{$product->code} - {$product->name}" : null;
                    })
                    ->options(function () {
                        return Product::where('active', 1)
                            ->limit(10)
                            ->get()
                            ->mapWithKeys(fn($product) => [
                                $product->id => "{$product->code} - {$product->name}"
                            ]);
                    })
                    ->multiple(),

                SelectFilter::make("store_id")
                    ->placeholder('Select Store')
                    ->label(__('lang.store'))
                    ->searchable()
                    ->default(fn() => request()->get('store_id'))
                    ->query(fn(Builder $q, $data) => $q)
                    ->options(Store::active()->get()->pluck('name', 'id')->toArray()),

                Filter::make('show_extra_fields')
                    ->label('Show Extra')
                    ->schema([
                        Toggle::make('only_available')
                            ->inline(false)
                            ->default(fn() => request()->get('only_available'))
                            ->label('Show Available in Stock')
                    ]),
            ], FiltersLayout::AboveContent);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListInventorySummaryReport::route('/'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return 'Fast';
    }

    public static function getNavigationBadgeColor(): string|array|null
    {
        return 'success';
    }
}
