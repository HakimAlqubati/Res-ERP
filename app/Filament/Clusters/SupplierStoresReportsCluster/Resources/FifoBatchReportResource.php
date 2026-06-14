<?php

namespace App\Filament\Clusters\SupplierStoresReportsCluster\Resources;

use App\Filament\Clusters\InventoryReportCluster;
use App\Filament\Clusters\SupplierStoresReportsCluster\Resources\FifoBatchReportResource\Pages\ListFifoBatchReport;
use App\Models\Category;
use App\Models\InventoryTransaction;
use App\Models\Product;
use App\Models\Store;
use Filament\Forms\Components\DatePicker;
use Filament\Pages\Enums\SubNavigationPosition;
use Filament\Resources\Resource;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class FifoBatchReportResource extends Resource
{
    protected static ?string $model = InventoryTransaction::class;
    protected static ?string $slug = 'fifo-batch-report';
    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static bool $shouldRegisterNavigation = false;

    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }

    public static function getLabel(): ?string
    {
        return 'FIFO Batch Report';
    }

    public static function getNavigationLabel(): string
    {
        return 'FIFO Batch Report';
    }

    public static function getPluralLabel(): ?string
    {
        return 'FIFO Batch Report';
    }

    protected static ?string $cluster = InventoryReportCluster::class;
    protected static ?\Filament\Pages\Enums\SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;
    protected static ?int $navigationSort = 8;

    public static function table(Table $table): Table
    {
        return $table->deferFilters(false)
            ->filters([
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
                SelectFilter::make('category_id')
                    ->label('Category')
                    ->options(Category::active()->pluck('name', 'id')->toArray())
                    ->searchable(),
                SelectFilter::make("store_id")
                    ->label(__('lang.store'))->searchable()->options(
                        Store::active()->get()->pluck('name', 'id')->toArray()
                    )->placeholder('Select Store'),
                Filter::make('date_range')
                    ->form([
                        DatePicker::make('date_from')->label('Date From'),
                        DatePicker::make('date_to')->label('Date To'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query; // handled in the page
                    }),
            ], FiltersLayout::AboveContent);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListFifoBatchReport::route('/'),
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
