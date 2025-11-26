<?php

namespace App\Filament\Resources\OrderResource\Schemas;

use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Grid;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\DateTimePicker;
use Filament\Schemas\Schema;
use App\Models\Branch;
use App\Models\Order;
use App\Models\Product;
use App\Models\Store;
use App\Models\UnitPrice;
use Filament\Schemas\Components\Utilities\Set;

class OrderForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Fieldset::make()->columnSpanFull()->schema([
                    Grid::make()->columnSpanFull()->columns(3)->schema([
                        Select::make('branch_id')->required()
                            ->label(__('lang.branch'))
                            ->options(Branch::where('active', 1)->get(['id', 'name'])->pluck('name', 'id')),
                        Select::make('status')->required()
                            ->label(__('lang.order_status'))
                            ->options(Order::getStatusLabels())->default(Order::ORDERED),
                        DateTimePicker::make('created_at')
                            ->label(__('lang.created_at')),
                        Select::make('stores')->multiple()->required()
                            ->label(__('lang.store'))
                            // ->disabledOn('edit')
                            ->options([
                                Store::active()
                                    // ->withManagedStores()
                                    ->get()->pluck('name', 'id')->toArray()
                            ])->hidden(),
                    ]),
                    // Repeater for Order Details
                    Repeater::make('orderDetails')->columnSpanFull()->hiddenOn(['view', 'edit'])
                        ->label(__('lang.order_details'))->columns(9)
                        ->relationship() // Relationship with the OrderDetails model
                        ->schema([
                            Select::make('product_id')
                                ->label(__('lang.product'))
                                ->searchable()
                                // ->disabledOn('edit')
                                ->options(function () {
                                    return Product::where('active', 1)
                                        ->unmanufacturingCategory()
                                        ->pluck('name', 'id');
                                })
                                ->getSearchResultsUsing(fn(string $search): array => Product::where('active', 1)
                                    ->unmanufacturingCategory()
                                    ->where('name', 'like', "%{$search}%")->limit(50)->pluck('name', 'id')->toArray())
                                ->getOptionLabelUsing(fn($value): ?string => Product::unmanufacturingCategory()->find($value)?->name)
                                ->reactive()
                                ->afterStateUpdated(fn(callable $set) => $set('unit_id', null))
                                ->searchable()->columnSpan(function ($record) {

                                    if ($record) {
                                        return 2;
                                    } else {
                                        return 3;
                                    }
                                })->required(),
                            Select::make('unit_id')
                                ->label(__('lang.unit'))
                                // ->disabledOn('edit')
                                ->options(
                                    function (callable $get) {

                                        $unitPrices = UnitPrice::where('product_id', $get('product_id'))->get()->toArray();

                                        if ($unitPrices)
                                            return array_column($unitPrices, 'unit_name', 'unit_id');
                                        return [];
                                    }
                                )
                                ->searchable()
                                ->reactive()
                                ->afterStateUpdated(function (Set $set, $state, $get) {
                                    $unitPrice = UnitPrice::where(
                                        'product_id',
                                        $get('product_id')
                                    )->where('unit_id', $state)->first();
                                    $set('price', $unitPrice->price);
                                    $set('total_price', ((float) $unitPrice->price) * ((float) $get('quantity')));
                                    $set('package_size',  $unitPrice->package_size ?? 0);
                                })->columnSpan(2)->required(),
                            TextInput::make('purchase_invoice_id')->label(__('lang.purchase_invoice_id'))->readOnly()->visibleOn('view'),
                            TextInput::make('package_size')->label(__('lang.package_size'))->readOnly()->columnSpan(1),
                            Hidden::make('available_quantity')
                                ->default(1),
                            TextInput::make('quantity')
                                ->label(__('lang.quantity'))
                                ->numeric()
                                ->live(onBlur: true)
                                ->afterStateUpdated(function (Set $set, $state, $get) {
                                    $set('available_quantity', $state);

                                    $set('total_price', ((float) $state) * ((float)$get('price') ?? 0));
                                })

                                ->required()->default(1),

                            TextInput::make('price')
                                ->label(__('lang.price'))->readOnly()
                                ->numeric()
                                ->required()->columnSpan(1),
                            TextInput::make('total_price')
                                ->label(__('lang.total_price'))
                                ->numeric()
                                ->readOnly()->columnSpan(1),
                        ])

                        ->createItemButtonLabel(__('lang.add_detail')) // Customize button label
                        ->required(),

                ])
            ]);
    }
}
