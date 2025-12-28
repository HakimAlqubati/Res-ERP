<?php

namespace App\Filament\Clusters\FinancialReportsCluster\Resources;

use App\Filament\Clusters\FinancialReportsCluster;
use App\Filament\Clusters\FinancialReportsCluster\Resources\IncomeStatementResource\Pages;
use App\Models\Branch;
use App\Models\FinancialTransaction;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Pages\Enums\SubNavigationPosition;
use Filament\Schemas\Components\Fieldset;

class IncomeStatementResource extends Resource
{
    protected static ?string $model = FinancialTransaction::class;

    protected static ?string $slug = 'income-statement';

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-document-chart-bar';

    protected static ?string $cluster = FinancialReportsCluster::class;

    protected static ?SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;

    protected static ?int $navigationSort = 0;

    public static function getLabel(): ?string
    {
        return __('Income Statement');
    }

    public static function getNavigationLabel(): string
    {
        return __('Income Statement');
    }

    public static function getPluralLabel(): ?string
    {
        return __('Income Statements');
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                // Custom view used
            ])
            ->deferFilters(false)
            ->filtersFormColumns(4)
            ->filters([
                // Report Type Filter
                Tables\Filters\Filter::make('report_type')
                    ->label(__('Report Type'))
                    ->form([
                        \Filament\Forms\Components\ToggleButtons::make('type')
                            ->label(__('Report Type'))
                            ->options([
                                'single' => __('Single Branch'),
                                'comparison' => __('Branch Comparison'),
                            ])
                            ->default('single')
                            ->inline()
                            ->icons([
                                'single' => 'heroicon-o-building-office',
                                'comparison' => 'heroicon-o-chart-bar',
                            ])
                            ->colors([
                                'single' => 'primary',
                                'comparison' => 'success',
                            ])
                            ->live(),
                    ]),

                // Date Range Filter
                Tables\Filters\Filter::make('date_range')
                    ->label(__('Date Range'))
                    ->form([
                        \Filament\Forms\Components\Select::make('month')
                            ->label(__('Month'))
                            ->options(fn() => getMonthOptionsBasedOnSettings())
                            ->default(now()->format('F Y'))
                            ->required(),
                    ]),

                // Single Branch Filter (hidden when report_type is 'comparison')
                Tables\Filters\SelectFilter::make('branch_id')
                    ->label(__('Branch'))
                    ->relationship('branch', 'name', fn($query) => $query->where('active', true)->where('type', 'branch'))
                    ->searchable()
                    ->preload()
                    ->visible(fn($livewire) => ($livewire->getTableFilterState('report_type')['type'] ?? 'single') === 'single'),

                // Multi-Branch Filter (hidden when report_type is 'single')
                Tables\Filters\SelectFilter::make('branch_ids')
                    ->label(__('Select Branches'))
                    ->multiple()
                    ->options(fn() => Branch::where('active', true)->where('type', 'branch')->pluck('name', 'id'))
                    ->searchable()
                    ->preload()
                    ->visible(fn($livewire) => ($livewire->getTableFilterState('report_type')['type'] ?? 'single') === 'comparison'),
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
            'index' => Pages\ListIncomeStatement::route('/'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canAccess(): bool
    {
        if (isSuperAdmin()) {
            return true;
        }
        return false;
    }
}
