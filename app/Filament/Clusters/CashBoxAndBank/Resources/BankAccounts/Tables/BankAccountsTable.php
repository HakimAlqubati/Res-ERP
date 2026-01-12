<?php

namespace App\Filament\Clusters\CashBoxAndBank\Resources\BankAccounts\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class BankAccountsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->striped()
            ->columns([
                TextColumn::make('name')
                    ->label(__('lang.bank_account_name'))
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                TextColumn::make('account_number')
                    ->label(__('lang.account_number'))
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->copyMessage(__('lang.copied')),

                TextColumn::make('iban')
                    ->label(__('lang.iban'))
                    ->searchable()
                    ->toggleable()
                    ->copyable()
                    ->copyMessage(__('lang.copied')),

                TextColumn::make('currency.currency_code')
                    ->label(__('lang.currency'))
                    ->badge()
                    ->sortable(),

                TextColumn::make('glAccount.account_name')
                    ->label(__('lang.gl_account'))
                    ->searchable()
                    ->toggleable()
                    ->limit(30),

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
