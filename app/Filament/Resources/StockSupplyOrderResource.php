<?php
namespace App\Filament\Resources;

use App\Filament\Clusters\SupplierCluster;
use App\Filament\Resources\StockSupplyOrderResource\Pages;
use App\Models\StockSupplyOrder;
use App\Models\Product;
use App\Models\Unit;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Repeater;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;

class StockSupplyOrderResource extends Resource
{
    protected static ?string $model = StockSupplyOrder::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?string $cluster = SupplierCluster::class;
    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                DatePicker::make('order_date')->required(),
                Select::make('supplier_id')
                    ->relationship('supplier', 'name')
                    ->searchable()
                    ->required(),
                Select::make('store_id')
                    ->relationship('store', 'name')
                    ->searchable()
                    ->required(),
                TextInput::make('notes')->maxLength(255),

                // **Repeater for Details**
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
                        TextInput::make('quantity')->numeric()->required(),
                        TextInput::make('price')->numeric()->required()->hidden(),
                    ])
                    ->columns(3)
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('order_date')->sortable(),
                TextColumn::make('supplier.name')->label('Supplier')->sortable(),
                TextColumn::make('store.name')->label('Store')->sortable(),
                TextColumn::make('notes'),
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
            'index' => Pages\ListStockSupplyOrders::route('/'),
            'create' => Pages\CreateStockSupplyOrder::route('/create'),
            'edit' => Pages\EditStockSupplyOrder::route('/{record}/edit'),
        ];
    }
}
