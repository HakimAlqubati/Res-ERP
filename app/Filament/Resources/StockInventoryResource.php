<?php
namespace App\Filament\Resources;

use App\Filament\Clusters\SupplierCluster;
use App\Filament\Resources\StockInventoryResource\Pages;
use App\Models\StockInventory;
use App\Models\Product;
use App\Models\Unit;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;

class StockInventoryResource extends Resource
{
    protected static ?string $model = StockInventory::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?string $cluster = SupplierCluster::class;
    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                DatePicker::make('inventory_date')
                    ->required()
                    ->label('Inventory Date'),
                    
                Select::make('store_id')
                    ->relationship('store', 'name')
                    ->searchable()
                    ->required()
                    ->label('Store'),
                    
                Select::make('responsible_user_id')
                    ->relationship('responsibleUser', 'name')
                    ->searchable()
                    ->required()
                    ->label('Responsible User'),
                    
                Toggle::make('finalized')
                    ->default(false)
                    ->label('Finalized'),

                // **Repeater for Inventory Details**
                Repeater::make('details')->columnSpanFull()
                    ->relationship('details')
                    ->schema([
                        Select::make('product_id')
                            ->label('Product')
                            ->options(Product::all()->pluck('name', 'id'))
                            ->searchable()
                            ->required(),
                            
                        Select::make('unit_id')
                            ->label('Unit')
                            ->options(Unit::all()->pluck('name', 'id'))
                            ->searchable()
                            ->required(),
                            
                        TextInput::make('system_quantity')
                            ->numeric()
                            ->disabled()
                            ->label('System Quantity'),
                            
                        TextInput::make('physical_quantity')
                            ->numeric()
                            ->required()
                            ->label('Physical Quantity'),
                            
                        TextInput::make('difference')
                            ->numeric()
                            ->disabled()
                            ->label('Difference')
                            ->default(fn ($state, $record) => $record ? ($record->physical_quantity - $record->system_quantity) : 0),
                    ])
                    ->columns(5)
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('inventory_date')->sortable()->label('Inventory Date'),
                TextColumn::make('store.name')->sortable()->label('Store'),
                TextColumn::make('responsibleUser.name')->sortable()->label('Responsible User'),
                IconColumn::make('finalized')->boolean()->label('Finalized'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListStockInventories::route('/'),
            'create' => Pages\CreateStockInventory::route('/create'),
            'edit' => Pages\EditStockInventory::route('/{record}/edit'),
        ];
    }
}
