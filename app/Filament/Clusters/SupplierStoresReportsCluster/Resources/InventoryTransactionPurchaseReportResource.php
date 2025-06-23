<?php

namespace App\Filament\Clusters\SupplierStoresReportsCluster\Resources;

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
use Filament\Pages\SubNavigationPosition;
use Filament\Resources\Resource;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class InventoryTransactionPurchaseReportResource extends Resource
{
    protected static ?string $model = InventoryTransaction::class;
    protected static ?string $slug = 'inventory-p-report';
    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static bool $shouldRegisterNavigation = false;
    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }
    public static function getLabel(): ?string
    {
        return 'Store Position Report';
    }
    public static function getNavigationLabel(): string
    {
        return 'Store Position Report';
    }
    public static function getPluralLabel(): ?string
    {
        return 'Store Position Report';
    }
    protected static ?string $cluster = InventoryReportCluster::class;
    protected static SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;
    protected static ?int $navigationSort = 7;


    public static function table(Table $table): Table
    {
        return $table
            ->filters([

                // SelectFilter::make("category_id")
                //     ->label(__('lang.category'))->searchable()
                //     ->query(function (Builder $q, $data) {
                //         return $q;
                //     })->options(Category::active()->get()->pluck('name', 'id')),
                SelectFilter::make("product_id")
                    ->label(__('lang.product'))->searchable()
                    ->query(function (Builder $q, $data) {
                        return $q;
                    })->getSearchResultsUsing(function (string $search): array {
                        return Product::where('active', 1)
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
                    }),
                SelectFilter::make('manufacturing_filter')
                    ->label('Product Type')
                    ->options([
                        'only_mana' => 'Manufactured',
                        'only_unmana' => 'Unmanufactured',
                    ])
                    ->default('all'),
                SelectFilter::make('category_id')
                    ->label('Category')
                    ->options(Category::active()->pluck('name', 'id')->toArray())
                    ->searchable(),
                SelectFilter::make("store_id")
                    ->label(__('lang.store'))->searchable()->options(
                        Store::active()->get()->pluck('name', 'id')->toArray()
                    )->placeholder('Select Store'),
                // Filter::make('show_extra_fields')
                //     ->label('Show Extra')
                //     ->form([
                //         Toggle::make('only_available')
                //             ->inline(false)
                //             ->label('Show Available in Stock')
                //     ]),
            ], FiltersLayout::AboveContent);
    }


    public static function getPages(): array
    {
        return [
            'index' => Pages\ListInventoryTransactionPurchaseReport::route('/'),
            'purchase-details' => Pages\PurchaseDetails::route('/purchase-details/{product}'),
            'order-details' => Pages\OrderDetails::route('/order-details/{product}'),
        ];
    }
    public static function getNavigationBadge(): ?string
    {
        return 'Report';
    }
    public static function canViewAny(): bool
    {
        if (isSuperAdmin() || isSystemManager()) {
            return true;
        }
        return false;
    }
    
}
