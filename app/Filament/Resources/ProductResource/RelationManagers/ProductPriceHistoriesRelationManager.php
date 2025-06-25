<?php

namespace App\Filament\Resources\ProductResource\RelationManagers;


use App\Models\ProductPriceHistory;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ProductPriceHistoriesRelationManager extends RelationManager
{
    protected static string $relationship = 'productPriceHistories';
    protected static ?string $label = 'Product Price History';
    protected static ?string $pluralLabel = 'Product Price History';
    protected static ?string $title = 'Product Price History';
    protected static ?string $recordTitleAttribute = 'id';



    public function table(Table $table): Table
    {
        return $table
            ->striped()
            ->paginated([10, 25, 50, 100])
            ->columns([

                // Tables\Columns\TextColumn::make('productItem.product.name')
                //     ->label('Component Product')
                //     ->tooltip(fn($record)=>$record?->productItem?->id)
                //     ->formatStateUsing(
                //         fn($state, $record) =>
                //         optional($record->productItem?->product)->name ?? '-'
                //     )
                //     ->sortable()
                //     ->limit(30)->visible(fn($livewire) => $livewire->getOwnerRecord()?->is_manufacturing),
                Tables\Columns\TextColumn::make('old_price')
                    ->label('Old Price')
                    ->formatStateUsing(fn($state) => number_format($state, 2)),

                Tables\Columns\TextColumn::make('new_price')
                    ->label('New Price')
                    ->formatStateUsing(fn($state) => number_format($state, 2)),
                // Tables\Columns\TextColumn::make('source_id')
                //     ->label('source_id'),

                Tables\Columns\TextColumn::make('unit.name')
                    ->label('Unit')

                    ->sortable(),

                Tables\Columns\TextColumn::make('note')
                    ->label('Note')
                    ->limit(100)
                    ->toggleable(),

                Tables\Columns\TextColumn::make('date')
                    ->label('Date')
                    ->date()
                    ->sortable(),
            ])
            ->filters([])
            ->headerActions([])
            ->actions([
                // Tables\Actions\EditAction::make(),
                // Tables\Actions\DeleteAction::make(),
                // Tables\Actions\RestoreAction::make(),
            ])
            ->bulkActions([
                // Tables\Actions\DeleteBulkAction::make(),
                // Tables\Actions\RestoreBulkAction::make(),
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}