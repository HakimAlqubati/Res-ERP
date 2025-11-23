<?php

namespace App\Filament\Clusters\FinancialReportsCluster\Resources;

use App\Filament\Clusters\FinancialReportsCluster;
use App\Filament\Clusters\FinancialReportsCluster\Resources\FinancialComparisonReportResource\Pages;
use App\Models\FinancialTransaction;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Pages\Enums\SubNavigationPosition;

class FinancialComparisonReportResource extends Resource
{
    protected static ?string $model = FinancialTransaction::class;

    protected static ?string $slug = 'financial-comparison-report';

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-scale';

    protected static ?string $cluster = FinancialReportsCluster::class;

    protected static ?SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;

    protected static ?int $navigationSort = 4;

    public static function getLabel(): ?string
    {
        return __('Financial Comparison Report');
    }

    public static function getNavigationLabel(): string
    {
        return __('Comparison Report');
    }

    public static function getPluralLabel(): ?string
    {
        return __('Financial Comparison Reports');
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                // Custom view used
            ])
            ->deferFilters(false)
            ->filtersFormColumns(3)
            ->filters([
                Tables\Filters\Filter::make('period_one')
                    ->label(__('Period 1'))
                    ->form([
                        \Filament\Forms\Components\DatePicker::make('start_date')
                            ->label(__('Start Date (Period 1)'))
                            ->default(now()->subMonth()->startOfMonth()),
                        \Filament\Forms\Components\DatePicker::make('end_date')
                            ->label(__('End Date (Period 1)'))
                            ->default(now()->subMonth()->endOfMonth()),
                    ]),
                Tables\Filters\Filter::make('period_two')
                    ->label(__('Period 2'))
                    ->form([
                        \Filament\Forms\Components\DatePicker::make('start_date')
                            ->label(__('Start Date (Period 2)'))
                            ->default(now()->startOfMonth()),
                        \Filament\Forms\Components\DatePicker::make('end_date')
                            ->label(__('End Date (Period 2)'))
                            ->default(now()->endOfMonth()),
                    ]),
                Tables\Filters\SelectFilter::make('branch_id')
                    ->label(__('Branch'))
                    ->relationship('branch', 'name')
                    ->searchable()
                    ->preload(),
            ])
            ->actions([
                // No actions
            ])
            ->bulkActions([
                // No bulk actions
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListFinancialComparisonReport::route('/'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }
}
