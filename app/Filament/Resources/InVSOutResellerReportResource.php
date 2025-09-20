<?php

namespace App\Filament\Resources;

use Filament\Pages\Enums\SubNavigationPosition;
use App\Filament\Resources\InVSReportResource\Pages\ListInVSReport;
use App\Filament\Clusters\InventoryReportCluster;
use App\Filament\Clusters\ResellersCluster;
use App\Filament\Resources\InVSReportResource\Pages;
use App\Filament\Resources\InVSReportResource\Pages\ListInVSOutResellerReport;
use App\Models\StockSupplyOrder;
use App\Models\Store;
use Carbon\Carbon;
use Filament\Forms;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class InVSOutResellerReportResource extends Resource
{
    protected static ?string $model = StockSupplyOrder::class;
    protected static ?string $slug = 'in-vs-out-resellers';

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static bool $shouldRegisterNavigation = true;
    public static function getNavigationLabel(): string
    {
        return 'DO & Invoice';
    }
    public static function getPluralLabel(): ?string
    {
        return 'DO & Invoice';
    }


    public static function getLabel(): ?string
    {
        return 'DO & Invoice';
    }
    protected static ?string $cluster = ResellersCluster::class;
    protected static ?\Filament\Pages\Enums\SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;
    protected static ?int $navigationSort = 30;
    public static function table(Table $table): Table
    {
        $currentMonthData = getEndOfMonthDate(Carbon::now()->year, Carbon::now()->month);
        return $table
            ->deferFilters(false)
            ->filters([

                Filter::make('date_range')
                    ->form([
                        DatePicker::make('from_date')
                            ->label('From Date')
                            ->default(now()->startOfMonth()),

                        DatePicker::make('to_date')
                            ->label('To Date')
                            ->default(now()),
                    ]),

                SelectFilter::make("store_id")
                    ->label(__('lang.reseller'))
                    ->searchable()->preload()
                    ->selectablePlaceholder(true)
                    ->placeholder('All')
                    ->query(function (Builder $q, $data) {
                        return $q;
                    })->options(
                        Store::active()
                            ->whereHas('branches', function ($q) {
                                $q->resellers(); // scopeResellers
                            })
                            ->get()->pluck('name', 'id')->toArray()
                    )

            ], FiltersLayout::AboveContent);
    }



    public static function getPages(): array
    {
        return [
            'index' => ListInVSOutResellerReport::route('/'),

        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return 'Report';
    }
}
