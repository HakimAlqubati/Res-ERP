<?php

namespace App\Filament\Clusters\FinanceFormattingCluster\Resources\Accounts\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class AccountsTable
{
    public static function configure(Table $table): Table
    {
        return $table->striped()
            ->paginated([10, 25, 50, 100])
            ->deferFilters(false)
            ->columns([
                \Filament\Tables\Columns\TextColumn::make('account_code')
                    ->searchable()
                    ->sortable(),
                \Filament\Tables\Columns\TextColumn::make('account_name')
                    ->searchable()
                    ->sortable(),
                \Filament\Tables\Columns\TextColumn::make('account_type')
                    ->badge()
                    ->formatStateUsing(fn($state) => \App\Models\Account::getAccountTypeLabel($state)),
                \Filament\Tables\Columns\TextColumn::make('parent.account_name')
                    ->label('Parent Account'),
                // \Filament\Tables\Columns\TextColumn::make('currency.currency_code')
                //     ->label('Currency'),
                \Filament\Tables\Columns\IconColumn::make('is_active')
                    ->boolean()->alignCenter(),
                // \Filament\Tables\Columns\IconColumn::make('allow_manual_entries')
                //     ->boolean(),
            ])->filtersFormColumns(3)
            ->filters([
                TrashedFilter::make(),
                \Filament\Tables\Filters\SelectFilter::make('account_type')
                    ->options(\App\Models\Account::getAccountTypes()),
                \Filament\Tables\Filters\SelectFilter::make('parent_id')
                    ->label('Parent Account')
                    ->relationship('parent', 'account_name')
                    ->searchable()
                    ->preload()
                    ->multiple(),
            ], FiltersLayout::Modal)
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ]);
    }
}
