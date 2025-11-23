<?php

namespace App\Filament\Clusters\FinancialReportsCluster\Resources;

use App\Filament\Clusters\FinancialReportsCluster;
use App\Filament\Clusters\FinancialReportsCluster\Resources\FinancialDetailsReportResource\Pages;
use App\Models\FinancialTransaction;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Pages\Enums\SubNavigationPosition;

class FinancialDetailsReportResource extends Resource
{
    protected static ?string $model = FinancialTransaction::class;

    protected static ?string $slug = 'financial-details-report';

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-table-cells';

    protected static ?string $cluster = FinancialReportsCluster::class;

    protected static ?SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;

    protected static ?int $navigationSort = 3;

    public static function getLabel(): ?string
    {
        return __('Financial Details Report');
    }

    public static function getNavigationLabel(): string
    {
        return __('Details Report');
    }

    public static function getPluralLabel(): ?string
    {
        return __('Financial Details Reports');
    }



    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                // Columns are not needed as we use a custom view
            ])
            ->deferFilters(false)
            ->filtersFormColumns(5)
            ->filters([
                Tables\Filters\Filter::make('transaction_date')
                    ->form([
                        \Filament\Forms\Components\DatePicker::make('from')
                            ->label(__('From')),
                        \Filament\Forms\Components\DatePicker::make('until')
                            ->label(__('Until')),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['from'],
                                fn(Builder $query, $date): Builder => $query->whereDate('transaction_date', '>=', $date),
                            )
                            ->when(
                                $data['until'],
                                fn(Builder $query, $date): Builder => $query->whereDate('transaction_date', '<=', $date),
                            );
                    }),
                Tables\Filters\SelectFilter::make('branch_id')
                    ->label(__('Branch'))
                    ->relationship('branch', 'name')
                    ->searchable()
                    ->preload(),
                Tables\Filters\SelectFilter::make('category_id')
                    ->label(__('Category'))
                    ->relationship('category', 'name')
                    ->searchable()
                    ->preload(),
                Tables\Filters\SelectFilter::make('type')
                    ->label(__('Type'))
                    ->options([
                        'income' => __('Income'),
                        'expense' => __('Expense'),
                    ]),
                Tables\Filters\SelectFilter::make('status')
                    ->label(__('Status'))
                    ->options([
                        'paid' => __('Paid'),
                        'pending' => __('Pending'),
                        'overdue' => __('Overdue'),
                    ])->hidden(),
            ])
            ->actions([
                // No actions for report view
            ])
            ->bulkActions([
                // No bulk actions for report view
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListFinancialDetailsReport::route('/'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }
}
