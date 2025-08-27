<?php

namespace App\Filament\Clusters\SupplierStoresReportsCluster\Resources;

use Filament\Pages\Enums\SubNavigationPosition;
use App\Filament\Clusters\SupplierStoresReportsCluster\Resources\InventoryTransactionReportResource\Pages\ListInventoryTransactionReport;
use App\Filament\Clusters\InventoryReportCluster;
use App\Filament\Clusters\SupplierStoresReportsCluster;
use App\Filament\Clusters\SupplierStoresReportsCluster\Resources\InventoryTransactionReportResource\Pages;
use App\Models\Category;
use App\Models\Inventory;
use App\Models\InventoryTransaction;
use App\Models\Product;
use App\Models\Store;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class InventoryTransactionReportResource extends Resource
{
    protected static ?string $model = InventoryTransaction::class;
    protected static ?string $slug = 'inventory-report';
    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-rectangle-stack';
    public static function getLabel(): ?string
    {
        return 'Inventory';
    }
    public static function getNavigationLabel(): string
    {
        return 'Inventory';
    }
    public static function getPluralLabel(): ?string
    {
        return 'Inventory';
    }
    protected static ?string $cluster = InventoryReportCluster::class;
    protected static ?\Filament\Pages\Enums\SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;
    protected static ?int $navigationSort = 1;
    protected static bool $shouldRegisterNavigation = false;


    public static function table(Table $table): Table
    {



        return $table
            ->filters([

                SelectFilter::make("category_id")
                    ->label(__('lang.category'))->searchable()
                    ->query(function (Builder $q, $data) {
                        return $q;
                    })->options(Category::active()->get()->pluck('name', 'id')),
                SelectFilter::make("product_id")
                    ->label(__('lang.product'))->searchable()
                    // ->default($productIds)
                    ->query(function (Builder $q, $data) {
                        return $q;
                    })->getSearchResultsUsing(function (string $search): array {
                        return Product::query()
                            ->where(function ($query) use ($search) {
                                $query->where('name', 'like', "%{$search}%")
                                    ->orWhere('code', 'like', "%{$search}%");
                            })
                            ->limit(50)
                            ->get()
                            ->mapWithKeys(fn($product) => [
                                $product->id => "{$product->code} - {$product->name}"
                            ])
                            ->toArray();
                    })
                    ->getOptionLabelUsing(fn($value): ?string => Product::find($value)?->code . ' - ' . Product::find($value)?->name)
                    ->options(function () {
                        return Product::where('active', 1)
                            ->get()
                            ->mapWithKeys(fn($product) => [
                                $product->id => "{$product->code} - {$product->name}"
                            ]);
                    })->multiple(),
                SelectFilter::make("store_id")->placeholder('Select Store')
                    ->label(__('lang.store'))->searchable()
                    ->default(fn () => request()->get('store_id'))
                    ->query(function (Builder $q, $data) {
                        return $q;
                    })->options(
                        Store::active()->get()->pluck('name', 'id')->toArray()
                    ),
                Filter::make('show_extra_fields')
                    ->label('Show Extra')
                    ->schema([
                        Toggle::make('only_available')
                            ->inline(false)
                            ->default(fn () => request()->get('only_available'))
                            ->label('Show Available in Stock')
                    ]),
            
            ], FiltersLayout::AboveContent);
    }


    public static function getPages(): array
    {
        return [
            'index' => ListInventoryTransactionReport::route('/'),
        ];
    }
    public static function getNavigationBadge(): ?string
    {
        return 'Report';
    }
}