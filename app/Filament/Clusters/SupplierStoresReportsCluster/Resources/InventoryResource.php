<?php

namespace App\Filament\Clusters\SupplierStoresReportsCluster\Resources;

use App\Filament\Clusters\SupplierStoresReportsCluster;
use App\Filament\Clusters\SupplierStoresReportsCluster\Resources\InventoryResource\Pages;
use App\Filament\Clusters\SupplierStoresReportsCluster\Resources\InventoryResource\RelationManagers;
use App\Models\Inventory;
use App\Models\InventoryTransaction;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Pages\SubNavigationPosition;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class InventoryResource extends Resource
{
    protected static ?string $model = InventoryTransaction::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    // protected static ?string $cluster = SupplierStoresReportsCluster::class;
    protected static SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;
    protected static ?int $navigationSort = 4;
    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                //
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table->striped()
            ->defaultSort('id', 'desc')
            ->columns([

                Tables\Columns\TextColumn::make('product.name')
                    ->label('Product'),

                Tables\Columns\TextColumn::make('movement_type_title')->alignCenter(true)
                    ->label('Movement Type')
                    ->sortable(),

                Tables\Columns\TextColumn::make('quantity')
                    ->label('Qty')->alignCenter(true)
                    ->sortable(),

                Tables\Columns\TextColumn::make('unit.name')
                    ->label('Unit'),

                Tables\Columns\TextColumn::make('package_size')->alignCenter(true)
                    ->label('Package Size'),

                Tables\Columns\TextColumn::make('movement_date')
                    ->label('Movement Date')
                    ->sortable(),

                Tables\Columns\TextColumn::make('remaining_qty')->hidden()
                    ->label('Remaining Qty')->alignCenter(true)
                    ->getStateUsing(fn($record) => $record->getRemainingQtyAttribute()),

                Tables\Columns\TextColumn::make('notes')
                    ->label('Notes'),
            ])
            ->filters([
                Filter::make('product')
                    ->label('Product')
                    ->query(fn(Builder $query, array $data) => $query->whereHas('product', fn($q) => $q->where('name', 'like', "%{$data['value']}%")))
                    ->form([
                        Forms\Components\TextInput::make('value')->label('Product Name'),
                    ]),
            ])
            ->actions([
                // Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    // Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListInventories::route('/'),
            // 'create' => Pages\CreateInventory::route('/create'),
            // 'edit' => Pages\EditInventory::route('/{record}/edit'),
        ];
    }
}
