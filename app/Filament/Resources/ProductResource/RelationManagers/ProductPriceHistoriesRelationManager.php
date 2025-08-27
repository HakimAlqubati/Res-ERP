<?php

namespace App\Filament\Resources\ProductResource\RelationManagers;


use Filament\Tables\Columns\TextColumn;
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
                TextColumn::make('old_price')
                    ->label('Old Price')
                    ->formatStateUsing(fn($state) => number_format($state, 2)),

                TextColumn::make('new_price')
                    ->label('New Price')
                    ->formatStateUsing(fn($state) => number_format($state, 2)),
                // Tables\Columns\TextColumn::make('source_id')
                //     ->label('source_id'),

                TextColumn::make('unit.name')
                    ->label('Unit')

                    ->sortable(),

                TextColumn::make('note')
                    ->label('Note')
                    ->limit(100)
                    ->toggleable(),

                TextColumn::make('date')
                    ->label('Date')
                    ->date()
                    ->sortable(),
            ])
            ->filters([])
            ->headerActions([])
            ->recordActions([
                // Tables\Actions\EditAction::make(),
                // Tables\Actions\DeleteAction::make(),
                // Tables\Actions\RestoreAction::make(),
            ])
            ->toolbarActions([
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