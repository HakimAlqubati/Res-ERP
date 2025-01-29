<?php

namespace App\Filament\Clusters\SupplierStoresReportsCluster\Resources;

use App\Filament\Clusters\SupplierStoresReportsCluster;
use App\Filament\Clusters\SupplierStoresReportsCluster\Resources\InventoryResource\Pages;
use App\Filament\Clusters\SupplierStoresReportsCluster\Resources\InventoryResource\RelationManagers;
use App\Models\Inventory;
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
    protected static ?string $model = Inventory::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $cluster = SupplierStoresReportsCluster::class;
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
            ->defaultSort('last_updated', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('product.name')
                    ->label('Product Name')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('unit.name')->alignCenter(true)
                    ->label('Unit Name')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('quantity')
                    ->label('Quantity')->alignCenter(true)
                    ->sortable(),

                Tables\Columns\TextColumn::make('last_updated')
                    ->label('Last Updated')
                    ->sortable(),
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
                    Tables\Actions\DeleteBulkAction::make(),
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
