<?php

namespace App\Filament\Resources\TestinfoList;

// use App\Filament\Resources\OrderReportsResource\Pages\ListReportProductQuantities;

use App\Filament\Resources\OrderReportsResource\Pages\ListReportProductQuantities;
use App\Filament\Resources\UnitResource\Pages\ListUnits;
use App\Models\Branch;
use App\Models\FakeModelReports\ReportProductQuantities;
use App\Models\Product;
use App\Models\User;
use Filament\Forms\Components\Builder;
use Filament\Forms\Components\DatePicker;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Filament\Tables;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;

class TestinfoListResource extends Resource
{
    protected static ?string $model = User::class;
    protected static ?string $slug = 'test-info-list';
    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    /**
     * @deprecated Use `getModelLabel()` instead.
     */
    public static function getLabel(): ?string
    {
        return __('lang.report_product_quantities') . ' as test';
    }
    public static function getNavigationLabel(): string
    {
        return __('lang.report_product_quantities') . ' as test';
    }

    public static function getPluralLabel(): ?string
    {
        return __('lang.report_product_quantities') . ' as test';
    }

  
    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                TextEntry::make('title')
                    ->columnSpanFull(),
                
            ]);
    }

    protected function getTableFilters(): array
    {
        return [

            SelectFilter::make("product_id")
                ->label(__('lang.product'))
                ->searchable()
                ->query(function (Builder $q, $data) {
                    return $q;
                })->options(Product::where('active', 1)
                    ->get()->pluck('name', 'id')),
            SelectFilter::make("branch_id")
                ->label(__('lang.branch'))
                ->multiple()
                ->query(function (Builder $q, $data) {
                    return $q;
                })->options(Branch::where('active', 1)
                    ->get()->pluck('name', 'id')),
            Filter::make('date')
                ->form([
                    DatePicker::make('start_date')
                        ->label(__('lang.start_date')),
                    DatePicker::make('end_date')
                        ->label(__('lang.end_date')),
                ])
                ->query(function (Builder $query, array $data): Builder {
                    return $query;
                }),
        ];
    }


    public static function getPages(): array
    {
        return [
            'index' => ListReportProductQuantities::route('/'),
        ];
    }
}
