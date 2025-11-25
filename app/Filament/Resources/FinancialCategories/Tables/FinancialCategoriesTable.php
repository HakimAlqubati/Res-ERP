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
use Filament\Tables\Enums\FiltersLayout;

class FinancialCategoriesTable
{
    public static function configure(Table $table): Table
    {
        return $table->striped()->defaultSort('id', 'desc')
            ->columns([
                TextColumn::make('name')
                    ->label('Category Name')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('parent.name')
                    ->label('Parent Category')
                    ->searchable()
                    ->sortable(),

                BadgeColumn::make('type')
                    ->label('Type')->alignCenter()
                    ->colors([
                        'success' => FinancialCategory::TYPE_INCOME,
                        'danger' => FinancialCategory::TYPE_EXPENSE,
                    ])
                    ->formatStateUsing(fn($state) => FinancialCategory::TYPES[$state] ?? $state)
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
            ])->deferFilters(false)
            ->filtersFormColumns(4)
            ->filters([
                SelectFilter::make('type')
                    ->options(FinancialCategory::TYPES)
                    ->label('Type'),

                SelectFilter::make('parent_only')
                    ->label('Show Parent Categories Only')
                    ->placeholder('All categories')
                    ->options([
                        'parents_only' => 'Parents only',
                        'all_categories' => 'All categories',
                    ])
                    ->default('parents_only')
                    ->query(function ($query, $state) {
                        $state = $state['value'] ?? null;
                        if ($state === 'parents_only') {
                            return $query->whereNull('parent_id');
                        } elseif ($state === 'all_categories') {
                            return $query;
                        }
                        return $query;
                    }),

                TernaryFilter::make('is_visible')
                    ->label('Visible for Manual Entry')
                    ->placeholder('All categories')
                    ->trueLabel('Visible only')
                    ->falseLabel('Hidden only'),

                TrashedFilter::make(),
            ], FiltersLayout::Modal)
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
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
            ]);
    }
}
