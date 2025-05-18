<?php

namespace App\Filament\Resources;

use App\Filament\Resources\StockTransferOrderResource\Pages;
use App\Filament\Resources\StockTransferOrderResource\RelationManagers;
use App\Models\Product;
use App\Models\StockTransferOrder;
use App\Models\UnitPrice;
use Filament\Forms;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Fieldset;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class StockTransferOrderResource extends Resource
{
    protected static ?string $model = StockTransferOrder::class;
    protected static ?string $slug = 'stock-transfer-orders';
    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Fieldset::make()->columnSpanFull()->schema([
                    Grid::make()->schema([
                        Select::make('from_store_id')
                            ->label('From Store')
                            ->relationship('fromStore', 'name')
                            ->required(),

                        Select::make('to_store_id')
                            ->label('To Store')
                            ->relationship('toStore', 'name')
                            ->required(),

                        DatePicker::make('date')
                            ->required()->default(now()),

                        Select::make('status')
                            ->required()
                            ->options([
                                'created' => 'Created',
                                'approved' => 'Approved',
                                'rejected' => 'Rejected',
                            ])
                            ->default('created'),

                        Textarea::make('notes')
                            ->label('Notes')
                            ->columnSpanFull(),
                    ])->columns(4),

                    Grid::make()->schema([
                        Repeater::make('details')
                            ->label('Transfer Details')
                            ->relationship()
                            ->schema([
                                Select::make('product_id')
                                    ->required()
                                    ->columnSpan(2)
                                    ->label('Product')
                                    ->options(function () {
                                        return Product::where('active', 1)
                                            ->get()
                                            ->mapWithKeys(fn($product) => [
                                                $product->id => "{$product->code} - {$product->name}"
                                            ]);
                                    })
                                    ->searchable()
                                    ->getSearchResultsUsing(function (string $search): array {
                                        return Product::where('active', 1)
                                            ->where(function ($query) use ($search) {
                                                $query->where('name', 'like', "%{$search}%")
                                                    ->orWhere('code', 'like', "%{$search}%");
                                            })
                                            ->limit(50)
                                            ->get()
                                            ->mapWithKeys(fn($product) => [
                                                $product->id => "{$product->code} - {$product->name}"
                                            ])
                                            ->toArray();
                                    })
                                    ->getOptionLabelUsing(fn($value): ?string => Product::find($value)?->code . ' - ' . Product::find($value)?->name)
                                    ->reactive()
                                    ->afterStateUpdated(function ($set, $state) {
                                        $set('unit_id', null);
                                        $product = Product::find($state);
                                        $set('waste_stock_percentage', $product?->waste_stock_percentage);
                                    }),

                                Select::make('unit_id')->label('Unit')
                                    ->options(function (callable $get) {
                                        $product = \App\Models\Product::find($get('product_id'));
                                        if (! $product) return [];

                                        return $product->unitPrices->pluck('unit.name', 'unit_id')->toArray();
                                    })
                                    ->searchable()
                                    ->reactive()
                                    ->afterStateUpdated(function (\Filament\Forms\Set $set, $state, $get) {
                                        $unitPrice = UnitPrice::where(
                                            'product_id',
                                            $get('product_id')
                                        )
                                            ->showInInvoices()
                                            ->where('unit_id', $state)->first();
                                        $set('price', $unitPrice->price);

                                        $set('total_price', ((float) $unitPrice->price) * ((float) $get('quantity')));
                                        $set('package_size',  $unitPrice->package_size ?? 0);
                                    })->columnSpan(2)->required(),

                                TextInput::make('package_size')->type('number')->readOnly()->columnSpan(1)
                                    ->label(__('lang.package_size')),

                                TextInput::make('quantity')
                                    ->numeric()
                                    ->required()
                                    ->minValue(0.1)
                                    ->label('Quantity'),
                                TextInput::make('waste_stock_percentage')
                                    ->label('Waste %')
                                    ->numeric()
                                    ->minValue(0)
                                    ->maxValue(100)
                                    ->suffix('%')
                                    ->default(function (callable $get) {
                                        $productId = $get('product_id');
                                        return \App\Models\Product::find($productId)?->waste_stock_percentage ?? 0;
                                    })
                                    ->columnSpan(1),
                                Textarea::make('notes')->label('Notes')->columnSpanFull(),

                            ])
                            ->minItems(1)
                            ->defaultItems(1)
                            ->columns(7)
                            ->columnSpanFull(),
                    ])->columns(4),
                ])
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table->defaultSort('id', 'desc')->striped()
            ->columns([
                TextColumn::make('id')->label('ID')->sortable()->searchable()->alignCenter(true)->toggleable(),
                TextColumn::make('fromStore.name')->label('From')->sortable()->searchable()->alignCenter(true)->toggleable(),
                TextColumn::make('toStore.name')->label('To')->sortable()->searchable()->alignCenter(true)->toggleable(),
                TextColumn::make('date')->date()->sortable()->searchable()->alignCenter(true)->toggleable(),
                TextColumn::make('status')->badge()->sortable()->searchable()->alignCenter(true)->toggleable(),
                TextColumn::make('created_at')->dateTime()->sortable()->searchable()->alignCenter(true)->toggleable(),
                TextColumn::make('details_count')->alignCenter(true)->toggleable(),
            ])
            ->filters([
                //
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

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListStockTransferOrders::route('/'),
            'create' => Pages\CreateStockTransferOrder::route('/create'),
            'edit' => Pages\EditStockTransferOrder::route('/{record}/edit'),
        ];
    }
}
