<?php

namespace App\Filament\Clusters\AccountingReports\Resources;

use App\Filament\Clusters\AccountingReports\AccountingReportsCluster;
use App\Filament\Clusters\AccountingReports\Resources\TrialBalanceResource\Pages;
use App\Models\JournalEntry;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Pages\Enums\SubNavigationPosition;
use Filament\Support\Icons\Heroicon;

class TrialBalanceResource extends Resource
{
    protected static ?string $model = JournalEntry::class;

    protected static ?string $slug = 'trial-balance';

    protected static string | \BackedEnum | null $navigationIcon = Heroicon::OutlinedScale;

    protected static ?string $cluster = AccountingReportsCluster::class;

    protected static ?SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;

    protected static ?int $navigationSort = 1;

    public static function getLabel(): ?string
    {
        return __('lang.trial_balance');
    }

    public static function getNavigationLabel(): string
    {
        return __('lang.trial_balance');
    }

    public static function getPluralLabel(): ?string
    {
        return __('lang.trial_balance');
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
                            ->required()
                            ->maxDate(fn($get) => $get('end_date')),

                        \Filament\Forms\Components\DatePicker::make('end_date')
                            ->label(__('lang.end_date'))
                            ->default(now()->endOfMonth())
                            ->required()
                            ->minDate(fn($get) => $get('start_date')),
                    ]),

                // Account Type Filter
                Tables\Filters\SelectFilter::make('account_type')
                    ->label(__('lang.account_type'))
                    ->options([
                        'asset' => __('lang.assets'),
                        'liability' => __('lang.liabilities'),
                        'equity' => __('lang.equity'),
                        'revenue' => __('lang.revenue'),
                        'expense' => __('lang.expenses'),
                    ])
                    ->placeholder(__('lang.all_account_types'))
                    ->columnSpan(1),

                // Show Zero Balances Toggle
                Tables\Filters\TernaryFilter::make('show_zero_balances')
                    ->label(__('lang.show_zero_balances'))
                    ->placeholder(__('lang.hide'))
                    ->trueLabel(__('lang.show'))
                    ->falseLabel(__('lang.hide'))
                    ->default(false)
                    ->columnSpan(1),
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
            'index' => Pages\ListTrialBalance::route('/'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canAccess(): bool
    {
        return true;
        if (isSuperAdmin()) {
            return true;
        }
        return false;
    }
}
