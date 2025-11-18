<?php

namespace App\Filament\Resources\ReturnedOrderResource\Schema;

use App\Filament\Resources\ReturnedOrderResource;
use App\Models\Order;
use App\Models\Product;
use App\Models\ReturnedOrder;
use App\Models\Store;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Repeater\TableColumn;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Schema;

class ReturnedOrderForm
{

    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Fieldset::make('Returned Order Info')->columnSpanFull()
                    ->schema([
                        Select::make('original_order_id')
                            ->label('Original Order')
                            ->relationship('order', 'id')
                            ->searchable()
                            ->required()->live()
                            ->getSearchResultsUsing(fn(string $search) => ReturnedOrderResource::getOrderSearchQuery($search))
                            ->afterStateUpdated(function ($state, $set) {
                                $order = Order::find($state);
                                if ($order && $order->branch_id) {
                                    $set('branch_id', $order->branch_id);
                                }

                                if ($order) {
                                    $details = $order->orderDetails->map(function ($detail) {
                                        return [
                                            'product_id'   => $detail->product_id,
                                            'unit_id'      => $detail->unit_id,
                                            'quantity'     => $detail->available_quantity,
                                            'price'        => $detail->price,
                                            'package_size' => $detail->package_size ?? 1,
                                            'notes'        => 'Auto-filled from order #' . $detail->order_id,
                                        ];
                                    })->toArray();

                                    $set('details', $details);
                                }
                            }),

                        Select::make('branch_id')
                            ->label('Branch')
                            ->required()
                            ->reactive()
                            ->relationship('branch', 'name')->disabled()->dehydrated(),
                        Select::make('store_id')
                            ->label('Store')
                            ->required()
                            ->options(Store::active()->get(['id', 'name'])->pluck('name', 'id')),
                        DatePicker::make('returned_date')
                            ->label('Returned Date')->default(now())
                            ->required(),

                        Select::make('status')
                            ->label('Status')->disabledOn('create')
                            ->options(ReturnedOrder::getStatusOptions())
                            ->default(ReturnedOrder::STATUS_CREATED),

                        Select::make('approved_by')
                            ->label('Approved By')
                            ->relationship('approver', 'name')
                            ->searchable()->hiddenOn('create'),

                        Textarea::make('reason')
                            ->label('Return Reason')->columnSpanFull()
                            ->rows(3),
                    ])->columns(5),

                Fieldset::make('Returned Products Details')->columnSpanFull()

                    ->schema([
                        Repeater::make('details')
                            ->relationship()
                            ->table([
                                TableColumn::make(__('lang.product'))->width('18rem'),
                                TableColumn::make(__('lang.unit'))->width('15rem'),
                                TableColumn::make(__('lang.quantity'))->width('8rem')->alignCenter(),
                                TableColumn::make(__('lang.psize'))->width('8rem')->alignCenter(),
                                TableColumn::make(__('lang.notes')),
                            ])

                            ->label('Returned Items')
                            ->columns(5)
                            ->schema([
                                Select::make('product_id')
                                    ->label('Product')
                                    ->searchable()
                                    ->options(function () {
                                        return Product::active()
                                            ->orderBy('id', 'asc')
                                            ->get(['id', 'code', 'name', 'active'])

                                            ->mapWithKeys(fn($product) => [
                                                $product->id => "{$product->code} - {$product->name}",
                                            ]);
                                    })
                                    ->getSearchResultsUsing(function (string $search): array {
                                        return Product::active()
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
                                    ->required()
                                    ->columnSpan(2),

                                Select::make('unit_id')->columnSpan(2)
                                    ->label('Unit')
                                    ->relationship('unit', 'name')
                                    ->searchable()
                                    ->required(),

                                TextInput::make('quantity')
                                    // ->extraAttributes(['class' => 'text-center'])
                                    ->extraInputAttributes(['class' => 'text-center'])
                                    ->label('Quantity')
                                    ->numeric()->live(onBlur: true)
                                    ->required()
                                    ->rules(function (callable $get) {
                                        $orderId   = $get('../../original_order_id');
                                        $productId = $get('product_id');
                                        $unitId    = $get('unit_id');

                                        if (! $orderId || ! $productId || ! $unitId) {
                                            return [];
                                        }

                                        $order = Order::with('orderDetails')->find($orderId);
                                        if (! $order) {
                                            return [];
                                        }

                                        $detail = $order->orderDetails->firstWhere(function ($d) use ($productId, $unitId) {
                                            return $d->product_id == $productId && $d->unit_id == $unitId;
                                        });

                                        return $detail
                                            ? ['max:' . $detail->available_quantity]
                                            : [];
                                    }),

                                Hidden::make('price'),

                                TextInput::make('package_size')
                                    ->label('Package Size')
                                    ->numeric()->readOnly()
                                    ->default(1)
                                    ->extraInputAttributes(['class' => 'text-center'])

                                    ->required(),

                                Textarea::make('notes')
                                    ->label('Notes')->columnSpanFull()
                                    ->rows(2),
                            ])
                            ->defaultItems(1)
                            ->createItemButtonLabel('Add Product')
                            ->columnSpanFull()
                    ])
            ]);
    }
}
