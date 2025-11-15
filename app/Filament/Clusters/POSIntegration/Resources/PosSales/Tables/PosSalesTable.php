<?php

namespace App\Filament\Clusters\POSIntegration\Resources\PosSales\Tables;

use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\IconColumn;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class PosSalesTable
{
    public static function configure(Table $table): Table
    {
        return $table->defaultSort('id', 'desc')
            ->deferFilters(false)
            ->striped()
            ->columns([
                TextColumn::make('id')
                    ->label('ID')
                    ->sortable()->alignCenter()->toggleable()
                    ->searchable(),

                TextColumn::make('formatted_sale_date')
                    ->label('Sale Date')
                    ->dateTime('Y-m-d H:i')
                    ->sortable(),

                TextColumn::make('branch.name')
                    ->label('Branch')
                    ->sortable()
                    ->searchable()
                    ->toggleable(),

                TextColumn::make('store.name')
                    ->label('Store')
                    ->sortable()
                    ->searchable()
                    ->toggleable(),

                BadgeColumn::make('status')->alignCenter()
                    ->label('Status')
                    ->formatStateUsing(fn($state, $record) => $record->status_label)
                    ->colors([
                        'gray'  => 'draft',
                        'green' => 'completed',
                        'red'   => 'cancelled',
                    ])
                    ->sortable(),
                TextColumn::make('items_count')
                    ->label('Items')
                    ->counts('items')      // يعدّ علاقة items
                    ->numeric(0)
                    ->alignCenter()
                    ->sortable(),
                // TextColumn::make('total_quantity')
                //     ->label('Total Qty')
                //     ->numeric(4)->alignCenter()
                //     ->sortable(),

                TextColumn::make('total_amount')->alignCenter()
                    ->label('Total Amount')
                    // ->money('USD') // أو SAR حسب مشروعك
                    ->sortable()->formatStateUsing(fn($state) => formatMoneyWithCurrency($state))
                // ->getStateUsing(fn($state) => formatMoneyWithCurrency($state))
                ,

                IconColumn::make('cancelled')
                    ->label('Cancelled?')->alignCenter()
                    ->boolean()
                    ->sortable()
                    ->tooltip(fn($record) => $record->cancel_reason ?? 'No reason'),

                TextColumn::make('createdBy.name')
                    ->label('Created By')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->searchable(),

                TextColumn::make('updatedBy.name')
                    ->label('Updated By')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->searchable(),

                TextColumn::make('notes')
                    ->label('Notes')
                    ->wrap()
                    ->limit(30)
                    ->toggleable(isToggledHiddenByDefault: true)
            ])
            ->filters([
                TrashedFilter::make(),
            ])
            ->recordActions([
                ViewAction::make(),
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
