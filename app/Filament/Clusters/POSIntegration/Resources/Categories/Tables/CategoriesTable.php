<?php

namespace App\Filament\Clusters\POSIntegration\Resources\Categories\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class CategoriesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->paginated([10, 25, 50, 100])

            ->columns([
                TextColumn::make('id')
                    ->sortable()->label(__('lang.id'))
                    ->searchable(isIndividual: true, isGlobal: false)->searchable(),
                TextColumn::make('name')->label(__('lang.name'))->sortable()
                    ->searchable(isIndividual: true, isGlobal: false),
                TextColumn::make('parent.name')->label(__('lang.parent_category'))->sortable()
                    ->searchable(isIndividual: true, isGlobal: false),
                // Tables\Columns\TextColumn::make('code')->label(__('lang.code'))
                //     ->searchable(isIndividual: true, isGlobal: false),
                TextColumn::make('code_starts_with')
                    ->label('Prefix Code')->sortable()
                    ->searchable()
                    ->tooltip('Used to auto-generate product codes')
                    ->alignCenter(true)->toggleable(),
                TextColumn::make('branch_names')
                    ->label('Customized for Branches')
                    ->limit(50)
                    ->toggleable(isToggledHiddenByDefault: true),
                // Tables\Columns\TextColumn::make('waste_stock_percentage')
                //     ->label('Waste %')
                //     ->toggleable(isToggledHiddenByDefault: true)
                //     ->alignCenter(true),

                TextColumn::make('description')->label(__('lang.description'))->toggleable()->toggleable(isToggledHiddenByDefault: true),
                // Tables\Columns\TextColumn::make('products')->label('Number of products'),

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
