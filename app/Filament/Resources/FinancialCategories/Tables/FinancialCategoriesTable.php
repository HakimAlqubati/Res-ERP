<?php

namespace App\Filament\Resources\FinancialCategories\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\DeleteAction;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\IconColumn;
use App\Models\FinancialCategory;

class FinancialCategoriesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Category Name')
                    ->searchable()
                    ->sortable(),

                BadgeColumn::make('type')
                    ->label('Type')->alignCenter()
                    ->colors([
                        'success' => FinancialCategory::TYPE_INCOME,
                        'danger' => FinancialCategory::TYPE_EXPENSE,
                    ])
                    ->formatStateUsing(fn ($state) => FinancialCategory::TYPES[$state] ?? $state)
                    ->sortable(),

                IconColumn::make('is_system')
                    ->label('System')
                    ->boolean()->alignCenter()
                    ->sortable(),

                IconColumn::make('is_visible')
                    ->label('Visible')
                    ->boolean()->alignCenter()
                    ->sortable(),

                TextColumn::make('transactions_count')
                    ->label('Transactions')->alignCenter()
                    ->counts('transactions')
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label('Created At')->alignCenter()
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('type')
                    ->options(FinancialCategory::TYPES)
                    ->label('Type'),

                TernaryFilter::make('is_system')
                    ->label('System Category')
                    ->placeholder('All categories')
                    ->trueLabel('System only')
                    ->falseLabel('Custom only'),

                TernaryFilter::make('is_visible')
                    ->label('Visible for Manual Entry')
                    ->placeholder('All categories')
                    ->trueLabel('Visible only')
                    ->falseLabel('Hidden only'),

                TrashedFilter::make(),
            ])
            ->recordActions([
                EditAction::make()
                    ->hidden(fn ($record) => $record->is_system === true),
                DeleteAction::make()
                    ->hidden(fn ($record) => $record->is_system === true),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->action(function ($records) {
                            $records->each(function ($record) {
                                if (!$record->is_system) {
                                    $record->delete();
                                }
                            });
                        }),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ])
            ->defaultSort('name', 'asc');
    }
}
