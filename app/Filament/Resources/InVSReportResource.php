<?php

namespace App\Filament\Resources;

use App\Filament\Clusters\InventoryReportCluster;
use App\Filament\Resources\InVSReportResource\Pages;
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

class InVSReportResource extends Resource
{
    protected static ?string $model = StockSupplyOrder::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static bool $shouldRegisterNavigation = false;
    public static function getNavigationLabel(): string
    {
        return 'In VS Out';
    }
    public static function getPluralLabel(): ?string
    {
        return 'In VS Out';
    }


    public static function getLabel(): ?string
    {
        return 'In VS Out';
    }
    protected static ?string $cluster = InventoryReportCluster::class;
    protected static SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;
    protected static ?int $navigationSort = 10;
    public static function table(Table $table): Table
    {
        $currentMonthData = getEndOfMonthDate(Carbon::now()->year, Carbon::now()->month);
        return $table

            ->filters([
                Filter::make('date')
                    ->form([
                        DatePicker::make('to_date')->live()

                            ->label('To Date')
                            ->default(now()),
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
            'index' => Pages\ListInVSReport::route('/'),

        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return 'Report';
    }
}
