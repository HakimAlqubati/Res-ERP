<?php

namespace App\Filament\Resources;

use Filament\Pages\Enums\SubNavigationPosition;
use App\Filament\Clusters\InventoryReportCluster;
use App\Filament\Resources\HalalLabelReportResource\Pages\ListHalalLabelReports;
use App\Models\Product;
use App\Models\StockSupplyOrder;
use App\Models\Store;
use Carbon\Carbon;
use Filament\Forms\Components\DatePicker;
use Filament\Resources\Resource;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class HalalLabelReportResource extends Resource
{
    protected static ?string $model = StockSupplyOrder::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-tag';
    protected static bool $shouldRegisterNavigation = false;

    public static function getNavigationLabel(): string
    {
        return 'Halal Label';
    }
    public static function getPluralLabel(): ?string
    {
        return 'Halal Label';
    }


    public static function getLabel(): ?string
    {
        return 'Halal Label';
    }
    protected static ?string $cluster = InventoryReportCluster::class;
    protected static ?\Filament\Pages\Enums\SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;
    protected static ?int $navigationSort = 11;

    public static function table(Table $table): Table
    {
        $currentMonthData = getEndOfMonthDate(Carbon::now()->year, Carbon::now()->month);
        return $table
            ->deferFilters(false)
            ->filters([
                Filter::make('date_range')
                    ->schema([
                        DatePicker::make('start_date')->live()
                            ->afterStateUpdated(function ($set, $state) {
                                // Assuming getEndOfMonthDate is a helper function available globally
                                $endNextMonthData = getEndOfMonthDate(Carbon::parse($state)->year, Carbon::parse($state)->month);
                                $set('end_date', $endNextMonthData['end_month']);
                            })
                            ->label('Start Date')
                            ->default($currentMonthData['start_month']),

                        DatePicker::make('end_date')
                            ->label('End Date')
                            ->default($currentMonthData['end_month']),
                    ]),
                SelectFilter::make("store_id")
                    ->label(__('lang.store'))->searchable()
                    ->placeholder('Choose')
                    ->query(function (Builder $q, $data) {
                        return $q;
                    })->options(
                        Store::active()->get()->pluck('name', 'id')->toArray()
                    ),
                SelectFilter::make("product_id")
                    ->label(__('lang.product'))
                    ->multiple()
                    ->searchable()
                    ->getSearchResultsUsing(
                        fn(string $search): array => Product::active()
                            ->manufacturingCategory()
                            ->where(function (Builder $q) use ($search) {
                                $q->where('name', 'like', "%{$search}%")
                                    ->orWhere('code', 'like', "%{$search}%");
                            })
                            ->limit(10)
                            ->get()
                            ->pluck('display_name', 'id')
                            ->toArray()
                    )
                    ->options(Product::active()->manufacturingCategory()->limit(10)->get()->pluck('display_name', 'id')->toArray())
                    ->getOptionLabelsUsing(fn(array $values): array => Product::whereIn('id', $values)->get()->pluck('display_name', 'id')->toArray()),
            ], FiltersLayout::AboveContent);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListHalalLabelReports::route('/'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return 'Report';
    }
}
