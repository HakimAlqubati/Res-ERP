<?php

namespace App\Filament\Resources;

use App\Filament\Clusters\InventoryReportCluster;
use App\Filament\Resources\StockSupplyOrderReportResource\Pages;
use App\Filament\Resources\StockSupplyOrderReportResource\RelationManagers;
use App\Models\StockSupplyOrder;
use App\Models\Store;
use Carbon\Carbon;
use Filament\Forms;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Form;
use Filament\Pages\SubNavigationPosition;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class StockSupplyOrderReportResource extends Resource
{
    protected static ?string $model = StockSupplyOrder::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static bool $shouldRegisterNavigation = false;
    public static function getNavigationLabel(): string
    {
        return 'Stock Supply';
    }
    public static function getPluralLabel(): ?string
    {
        return 'Stock Supply';
    }


    public static function getLabel(): ?string
    {
        return 'Stock Supply';
    }
    protected static ?string $cluster = InventoryReportCluster::class;
    protected static SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;
    protected static ?int $navigationSort = 10;
    public static function table(Table $table): Table
    {
        $currentMonthData = getEndOfMonthDate(Carbon::now()->year, Carbon::now()->month);
        return $table

            ->filters([
                Filter::make('date_range')
                    ->form([
                        DatePicker::make('start_date')->live()
                            ->afterStateUpdated(function ($set, $state) {
                                $endNextMonthData = getEndOfMonthDate(Carbon::parse($state)->year, Carbon::parse($state)->month);
                                $set('end_date', $endNextMonthData['end_month']);
                            })
                            ->label('Start Date')
                            ->default($currentMonthData['start_month']), // Use function for dynamic default value

                        DatePicker::make('end_date')
                            ->label('End Date')
                            ->default($currentMonthData['end_month']), // Use function for dynamic default value
                    ]),
                SelectFilter::make("store_id")
                    ->label(__('lang.store'))->searchable()
                    // ->selectablePlaceholder(false)
                    ->placeholder('Choose')
                    ->query(function (Builder $q, $data) {
                        return $q;
                    })->options(
                        Store::active()->get()->pluck('name', 'id')->toArray()
                    )

            ], FiltersLayout::AboveContent);
    }



    public static function getPages(): array
    {
        return [
            'index' => Pages\ListStockSupplyOrderReports::route('/'),

        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return 'Report';
    }
}
