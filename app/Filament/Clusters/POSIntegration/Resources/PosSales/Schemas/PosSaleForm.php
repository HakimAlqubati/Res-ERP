<?php

namespace App\Filament\Clusters\POSIntegration\Resources\PosSales\Schemas;

use App\Models\Branch;
use App\Models\Store;
use App\Models\Product;
use App\Models\Unit;
use App\Models\PosSale;
use App\Models\UnitPrice;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Repeater\TableColumn;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;

class PosSaleForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([

                /*
                |--------------------------------------------------------------------------
                | Sale Header
                |--------------------------------------------------------------------------
                */
                Fieldset::make('Sale Info')
                    ->columnSpanFull()
                    ->columns(3)
                    ->schema([
                        DatePicker::make('sale_date')
                            ->label('Sale Date')
                            ->required()
                             ->default(now()),

                        Select::make('branch_id')
                            ->label('Branch')
                            ->required()
                            ->searchable()->live()
                            ->afterStateUpdated(function ($state, $set) {
                                $branch = Branch::find($state);
                                $store = $branch?->store;
                                if ($store) {
                                    $set('store_id', $store->id);
                                }
                            })
                            ->options(
                                Branch::query()
                                    ->branches()
                                    ->orderBy('name')
                                    ->pluck('name', 'id')
                            ),

                        Hidden::make('store_id'),

                        Select::make('status')
                            ->label('Status')
                            ->columnSpan(1)
                            ->required()
                            ->disabled()
                            ->dehydrated()
                            ->options(PosSale::getStatusLabels())
                            ->default(PosSale::STATUS_DRAFT),
                        Textarea::make('notes')
                            ->label('Notes')
                            ->rows(2)
                            ->columnSpanFull(),

                        Placeholder::make('total_summary')
                            ->label('Totals')
                            ->content(function ($record) {
                                if (! $record) {
                                    return '-';
                                }

                                $qty    = number_format((float) $record->total_quantity, 4);
                                $amount = number_format((float) $record->total_amount, 2);

                                return "Qty: {$qty} | Amount: {$amount}";
                            })
                            ->columnSpanFull()
                            ->visibleOn('edit'),
                    ]),

                /*
                |--------------------------------------------------------------------------
                | Sale Items (Repeater)
                |--------------------------------------------------------------------------
                */
                Fieldset::make('Items')
                    ->columnSpanFull()
                    ->visibleOn('create')
                    ->schema([
                        Repeater::make('items')
                            ->relationship('items')
                            ->label('Sale Items')
                            ->columnSpanFull()
                            ->columns(5)
                            ->minItems(1)
                            ->createItemButtonLabel('Add Item')
                            ->table([
                                TableColumn::make('Product')->width('24rem'),
                                TableColumn::make('Unit')->alignCenter()->width('10rem'),
                                TableColumn::make('Qty')->alignCenter()->width('8rem'),
                                TableColumn::make('Price')->alignCenter()->width('10rem'),
                                TableColumn::make('Total')->alignCenter()->width('10rem'),
                            ])
                            ->schema([
                                Select::make('product_id')
                                    ->label('Product')
                                    ->required()
                                    ->searchable()
                                    ->options(function () {
                                        return Product::query()
                                            ->where('active', 1)
                                            ->orderBy('name')
                                            ->get()
                                            ->mapWithKeys(fn($product) => [
                                                $product->id => "{$product->code} - {$product->name}",
                                            ])
                                            ->toArray();
                                    })
                                    ->getSearchResultsUsing(function (string $search): array {
                                        return Product::query()
                                            ->where('active', 1)
                                            ->pos()
                                            ->where(function ($query) use ($search) {
                                                $query->where('name', 'like', "%{$search}%")
                                                    ->orWhere('code', 'like', "%{$search}%");
                                            })
                                            ->limit(50)
                                            ->get()
                                            ->mapWithKeys(fn($product) => [
                                                $product->id => "{$product->code} - {$product->name}",
                                            ])
                                            ->toArray();
                                    })
                                    ->getOptionLabelUsing(fn($value): ?string => Product::find($value)?->code . ' - ' . Product::find($value)?->name)
                                    ->columnSpan(2)
                                    ->live()->afterStateUpdated(function ($state, $set) {
                                        // $unitPrices = UnitPrice::where('product_id',$state)->get();
                                        $set('unit_id', null);
                                    }),

                                Select::make('unit_id')
                                    ->label('Unit')
                                    ->required()
                                    ->searchable()
                                    ->options(function (callable $get) {
                                        $product = Product::find($get('product_id'));
                                        if (! $product) return [];

                                        return $product->unitPrices
                                            ->pluck('unit.name', 'unit_id')?->toArray() ?? [];
                                    })
                                    ->columnSpan(1),

                                TextInput::make('quantity')
                                    ->label('Qty')
                                    ->numeric()
                                    ->default(1)
                                    ->required()
                                    ->minValue(0.0001)
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(function (Set $set, $state, Get $get) {
                                        $qty   = (float) $state;
                                        $price = (float) $get('price');

                                        $total = round($qty * $price, 8);
                                        $set('total_price', $total);
                                    })
                                    ->columnSpan(1),

                                TextInput::make('price')
                                    ->label('Price')
                                    ->numeric()
                                    ->default(0)
                                    ->required()
                                    ->minValue(0)
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(function (Set $set, $state, Get $get) {
                                        $qty   = (float) $get('quantity');
                                        $price = (float) $state;

                                        $total = round($qty * $price, 8);
                                        $set('total_price', $total);
                                    })
                                    ->columnSpan(1),

                                TextInput::make('total_price')
                                    ->label('Total')
                                    ->numeric()
                                    ->default(0)
                                    ->dehydrated()
                                    ->extraInputAttributes(['readonly' => true])
                                    ->columnSpan(1),
                            ]),
                    ]),
            ]);
    }
}
