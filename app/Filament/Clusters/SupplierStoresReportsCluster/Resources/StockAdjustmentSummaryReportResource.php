<?php

namespace App\Filament\Clusters\SupplierStoresReportsCluster\Resources;

use Filament\Pages\Enums\SubNavigationPosition;
use App\Filament\Clusters\SupplierStoresReportsCluster\Resources\StockAdjustmentReportResource\Pages\ListStockAdjustmentSummaryReports;
use App\Filament\Clusters\SupplierStoresReportsCluster\Resources\StockAdjustmentReportResource\Pages\ViewStockAdjustmentSummaryReport;
use App\Filament\Clusters\InventoryReportCluster;
use App\Filament\Clusters\SupplierStoresReportsCluster\Resources\StockAdjustmentReportResource\Pages;
use App\Models\StockAdjustmentDetail;
use App\Models\Store;
use Filament\Forms\Components\DatePicker;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Support\Collection;

class StockAdjustmentSummaryReportResource extends Resource
{
    protected static ?string $model = StockAdjustmentDetail::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-chart-bar';
    protected static ?string $cluster = InventoryReportCluster::class;

    protected static ?\Filament\Pages\Enums\SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;

    protected static ?int $navigationSort = 10;
    protected static bool $shouldRegisterNavigation = true;

    public static function getPluralLabel(): ?string
    {
        return 'Stock Adjustment Summary';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Stock Adjustment Summary';
    }

    public static function table(Table $table): Table
    {
        return $table
            ->deferFilters(false)
            ->paginated(false)
            ->filters([
                SelectFilter::make('product.category_id')
                    ->label('Category')
                    ->relationship('product.category', 'name')
                    ->multiple()
                    ->searchable()
                    ->preload()
                    ->placeholder('All Categories'),
                SelectFilter::make('adjustment_type')
                    ->label('Adjustment Type')
                    ->options([
                        'increase' => 'Increase',
                        'decrease' => 'Decrease',
                        // 'equal' => 'Equal',
                    ])
                    ->placeholder('All')
                    ->default(null),

                SelectFilter::make('store_id')
                    ->label('Store')
                    ->options(Store::active()->pluck('name', 'id')->toArray())
                    ->searchable()
                    ->preload()
                    ->placeholder('All Stores'),

                Filter::make('from_date')
                    ->label('From Date')
                    ->schema([
                        DatePicker::make('from_date')->maxDate(now())
                            ->default(now()),
                    ])
                    ->indicateUsing(function (array $data): ?string {
                        return $data['from_date'] ? 'From: ' . $data['from_date'] : null;
                    }),

                Filter::make('to_date')
                    ->label('To Date')
                    ->schema([
                        DatePicker::make('to_date')
                            // ->maxDate(now())
                            ->default(now()),
                    ])
                    ->indicateUsing(function (array $data): ?string {
                        return $data['to_date'] ? 'To: ' . $data['to_date'] : null;
                    }),
            ], layout: FiltersLayout::AboveContent);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListStockAdjustmentSummaryReports::route('/'),
            'view' => ViewStockAdjustmentSummaryReport::route('/view/{categoryId}/{adjustment_type}/{storeId}/{fromDate?}/{toDate?}'),


        ];
    }

    public static function canDeleteAny(): bool
    {
        return false;
    }

    public static function getNavigationBadge(): ?string
    {
        return null;
    }
}
