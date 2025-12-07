<?php

namespace App\Filament\Clusters\FinancialReportsCluster\Resources;

use App\Filament\Clusters\FinancialReportsCluster;
use App\Filament\Clusters\FinancialReportsCluster\Resources\FinancialCategoryReportResource\Pages\ListFinancialCategoryReport;
use App\Models\FinancialCategory;
use App\Models\Branch;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Toggle;
use Filament\Pages\Enums\SubNavigationPosition;
use Filament\Resources\Resource;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class FinancialCategoryReportResource extends Resource
{
    protected static ?string $model = FinancialCategory::class;

    protected static ?string $slug = 'financial-category-report';

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-document-chart-bar';

    protected static ?string $cluster = FinancialReportsCluster::class;

    protected static ?SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;

    protected static ?int $navigationSort = 1;

    public static function getLabel(): ?string
    {
        return __('Financial Category Report');
    }

    public static function getNavigationLabel(): string
    {
        return __('Category Report');
    }

    public static function getPluralLabel(): ?string
    {
        return __('Financial Category Reports');
    }

    public static function table(Table $table): Table
    {
        return $table->deferFilters(false)
            ->filters([
                SelectFilter::make('type')
                    ->label(__('Type'))
                    ->options([
                        'income' => __('Income'),
                        'expense' => __('Expense'),
                    ])
                    ->query(function (Builder $q, $data) {
                        return $q;
                    }),

                SelectFilter::make('branch_id')
                    ->label(__('Branch'))
                    ->searchable()
                    ->query(function (Builder $q, $data) {
                        return $q;
                    })
                    ->options(Branch::active()->branches()->get()->pluck('name', 'id')),

                Filter::make('date_range')
                    ->label(__('Date Range'))
                    ->schema([
                        DatePicker::make('start_date')
                            ->label(__('Start Date'))
                            ->default(fn() => request()->get('start_date') ?? now()->startOfMonth()->format('Y-m-d')),
                        DatePicker::make('end_date')
                            ->label(__('End Date'))
                            ->default(fn() => request()->get('end_date') ?? now()->endOfMonth()->format('Y-m-d')),
                    ])
                    ->columnSpan(2),

                SelectFilter::make('status')
                    ->label(__('Transaction Status'))
                    ->options([
                        'paid' => __('Paid'),
                        'pending' => __('Pending'),
                        'overdue' => __('Overdue'),
                    ])
                    ->query(function (Builder $q, $data) {
                        return $q;
                    }),


            ], FiltersLayout::AboveContent);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListFinancialCategoryReport::route('/'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return __('Report');
    }

    public static function canViewAny(): bool
    {
        return isSuperAdmin() || isSystemManager() || isFinanceManager();
    }
    public static function canAccess(): bool
    {
        if (isSuperAdmin()) {
            return true;
        }
        return false;
    }
}
