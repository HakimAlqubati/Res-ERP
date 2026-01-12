<?php

namespace App\Filament\Clusters\AccountingReports\Resources;

use App\Filament\Clusters\AccountingReports\AccountingReportsCluster;
use App\Filament\Clusters\AccountingReports\Resources\AccountingIncomeStatementResource\Pages;
use App\Models\Branch;
use App\Models\JournalEntry;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Pages\Enums\SubNavigationPosition;
use Filament\Support\Icons\Heroicon;

class AccountingIncomeStatementResource extends Resource
{
    protected static ?string $model = JournalEntry::class;

    protected static ?string $slug = 'income-statement';

    protected static string | \BackedEnum | null $navigationIcon = Heroicon::OutlinedDocumentChartBar;

    protected static ?string $cluster = AccountingReportsCluster::class;

    protected static ?SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;

    protected static ?int $navigationSort = 0;

    public static function getLabel(): ?string
    {
        return __('lang.income_statement');
    }

    public static function getNavigationLabel(): string
    {
        return __('lang.income_statement');
    }

    public static function getPluralLabel(): ?string
    {
        return __('lang.income_statement_report');
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
                // Date Range Filter
                Tables\Filters\Filter::make('date_range')
                    ->label(__('lang.date_range'))
                    ->columnSpan(2)
                    ->form([
                        \Filament\Forms\Components\DatePicker::make('start_date')
                            ->label(__('lang.start_date'))
                            ->default(now()->startOfMonth())
                            ->required(),

                        \Filament\Forms\Components\DatePicker::make('end_date')
                            ->label(__('lang.end_date'))
                            ->default(now()->endOfMonth())
                            ->required(),
                    ]),

                // Branch Filter
                Tables\Filters\SelectFilter::make('branch_id')
                    ->label(__('lang.branch'))
                    ->options(fn() => Branch::where('type', 'branch')->pluck('name', 'id'))
                    ->searchable()
                    ->preload()
                    ->placeholder(__('lang.all_branches'))
            ])
            ->actions([])
            ->bulkActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAccountingIncomeStatement::route('/'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canAccess(): bool
    {
        return true;
    }
}
