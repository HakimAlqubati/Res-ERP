<?php

namespace App\Filament\Clusters\CashBoxAndBank\Resources\CashBoxes\Tables;

use App\ValueObjects\Money;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class CashBoxesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->striped()
            ->columns([
                TextColumn::make('name')
                    ->label(__('lang.cash_box_name'))
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                TextColumn::make('currency.currency_code')
                    ->label(__('lang.currency'))
                    ->badge()
                    ->sortable(),

                TextColumn::make('glAccount.account_name')
                    ->label(__('lang.gl_account'))
                    ->searchable()
                    ->toggleable()
                    ->limit(30),

                TextColumn::make('keeper.name')
                    ->label(__('lang.keeper'))
                    ->searchable()
                    ->sortable()
                    ->default('-')
                    ->toggleable(),

                TextColumn::make('max_limit')
                    ->label(__('lang.max_limit'))
                    ->formatStateUsing(
                        fn($state, $record) =>
                        $state > 0
                            ? Money::fromCurrency($state, $record->currency)
                            : __('lang.no_limit')
                    )
                    ->sortable()
                    ->toggleable(),

                IconColumn::make('is_active')
                    ->label(__('lang.is_active'))
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger')
                    ->alignCenter(),

                TextColumn::make('created_at')
                    ->label(__('lang.created_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('updated_at')
                    ->label(__('lang.updated_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                TrashedFilter::make(),
            ])
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
