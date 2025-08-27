<?php

namespace App\Filament\Clusters\SupplierStoresReportsCluster\Resources;

use Filament\Pages\Enums\SubNavigationPosition;
use App\Filament\Clusters\SupplierStoresReportsCluster\Resources\InventoryWithUsageReportResource\Pages\ListInventoryWithUsageReport;
use App\Filament\Clusters\InventoryReportCluster;
use App\Filament\Clusters\SupplierStoresReportsCluster;
use App\Filament\Clusters\SupplierStoresReportsCluster\Resources\InventoryWithUsageReportResource\Pages;
use App\Models\Category;
use App\Models\InventoryTransaction;
use App\Models\Product;
use App\Models\Store;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\HtmlString;

class InventoryWithUsageReportResource extends Resource
{
    protected static ?string $model = InventoryTransaction::class;
    protected static ?string $slug = 'inventory-with-usage-report';
    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-chart-bar';
protected static bool $shouldRegisterNavigation = false;
    public static function getLabel(): ?string
    {
        return 'Manufacturing Store Position Report';
    }

    public static function getNavigationLabel(): string
    {
        return 'Manufacturing Store Position Report';
    }

    public static function getPluralLabel(): ?string
    {
        return 'Manufacturing Store Position Report';
    }

    protected static ?string $cluster = InventoryReportCluster::class;
    protected static ?\Filament\Pages\Enums\SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;
    protected static ?int $navigationSort = 6;

    public static function table(Table $table): Table
    {
        return $table
            ->filters([
                SelectFilter::make("category_id")
                    ->label(__('lang.category'))->searchable()
                    ->options(Category::active()->get()->pluck('name', 'id')),

                SelectFilter::make("product_id")
                    ->label(__('lang.product'))->searchable()
                    ->getSearchResultsUsing(function (string $search): array {
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
                    }),

                SelectFilter::make("store_id")->placeholder('Select Store')
                    ->label(__('lang.store'))->searchable()
                    ->options(Store::active()->where('is_central_kitchen', 1)->get()->pluck('name', 'id')->toArray()),
                Filter::make('show_extra_fields')
                    ->label('Show Smallest Unit')
                    ->schema([
                        Toggle::make('only_smallest_unit')
                            ->inline(false)
                            ->label(new HtmlString('Show Only Smallest Unit<br>Show Total'))
                    ]),
            ], FiltersLayout::AboveContent);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListInventoryWithUsageReport::route('/'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return 'Report';
    }
}
