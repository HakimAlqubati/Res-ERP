<?php

namespace App\Filament\Clusters\FinancialReportsCluster\Resources;

use App\Filament\Clusters\FinancialReportsCluster;
use App\Filament\Clusters\FinancialReportsCluster\Resources\IncomeStatementResource\Pages;
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
            ->filtersFormColumns(3)
            ->filters([
                     Tables\Filters\Filter::make('date_range')
                        ->label(__('Date Range'))
                        ->schema([
                            \Filament\Forms\Components\DatePicker::make('start_date')
                                ->label(__('From'))
                                ->displayFormat('Y-m-d')
                                ->format('Y-m-d')
                                ->default(now()->subMonth()->startOfMonth())
                                ->live()
                                ->afterStateUpdated(function ($get,  $set, ?string $state) {
                                    if (! $state) return;
                                    $date = \Carbon\Carbon::parse($state);
                                    $set('end_date', $date->endOfMonth()->format('Y-m-d'));
                                }),
                            \Filament\Forms\Components\DatePicker::make('end_date')
                                ->label(__('To'))
                                ->displayFormat('Y-m-d')
                                ->format('Y-m-d')
                                ->default(now()->subMonth()->endOfMonth())
                                ->live()
                                ->afterStateUpdated(function ($get,  $set, ?string $state) {
                                    if (! $state) return;
                                    $date = \Carbon\Carbon::parse($state);
                                    $set('start_date', $date->startOfMonth()->format('Y-m-d'));
                                }),
                        ]),
                    Tables\Filters\SelectFilter::make('branch_id')
                        ->label(__('Branch'))
                        ->relationship('branch', 'name', fn($query) => $query->where('active', true)->where('type', 'branch'))
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
            'index' => Pages\ListIncomeStatement::route('/'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }
}
