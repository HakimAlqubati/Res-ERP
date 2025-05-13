<?php

namespace App\Filament\Resources;

use App\Filament\Clusters\OrderReportsCluster;
use App\Filament\Resources\ReturnedOrderReportResource\Pages;
use App\Filament\Resources\ReturnedOrderReportResource\RelationManagers;
use App\Models\Branch;
use App\Models\FakeModelReports\ReturnedOrderReport;
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

class ReturnedOrderReportResource extends Resource
{
    protected static ?string $model = ReturnedOrderReport::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $cluster = OrderReportsCluster::class;
    protected static SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;
    protected static ?int $navigationSort = 4;



    public static function table(Table $table): Table
    {
        return $table
            ->filters([
                SelectFilter::make("branch_id")
                    ->label(__('lang.branch'))
                    ->options(Branch::whereIn('type', [Branch::TYPE_BRANCH, Branch::TYPE_CENTRAL_KITCHEN])->active()
                        ->get()->pluck('name', 'id')),
                Filter::make('date')
                    ->form([
                        DatePicker::make('start_date')->default(now()->firstOfMonth())
                            ->label(__('lang.start_date')),
                        DatePicker::make('end_date')->default(now()->endOfMonth())
                            ->label(__('lang.end_date')),
                    ])
                    ->query(function (\Illuminate\Database\Eloquent\Builder $query, array $data): \Illuminate\Database\Eloquent\Builder {
                        return $query;
                    })
            ], FiltersLayout::AboveContent);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListReturnedOrderReports::route('/'),
            'details' => Pages\ReturnedOrdersDetailsPage::route('/details/{id}'),


        ];
    }
}
