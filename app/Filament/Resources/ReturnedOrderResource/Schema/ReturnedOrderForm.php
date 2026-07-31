<?php

namespace App\Filament\Resources\ReturnedOrderResource\Schema;

use App\Filament\Resources\ReturnedOrderResource;
use App\Models\Order;
use App\Models\Product;
use App\Models\ReturnedOrder;
use App\Models\Store;
use App\Models\InventoryTransaction;
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
                                    $transaction = InventoryTransaction::where('transactionable_type', Order::class)
                                        ->where('transactionable_id', $order->id)
                                        ->where('movement_type', 'out')
                                        ->first();
                                        
                                    if ($transaction && $transaction->store_id) {
                                        $set('store_id', $transaction->store_id);
                                    }

                                    $returnedDetails = \App\Models\ReturnedOrderDetail::whereHas('returnedOrder', function($q) use ($order) {
                                        $q->where('original_order_id', $order->id)
                                          ->where('status', '!=', \App\Models\ReturnedOrder::STATUS_REJECTED);
                                    })
                                    ->select('product_id', 'unit_id', \Illuminate\Support\Facades\DB::raw('SUM(quantity) as total_returned'))
                                    ->groupBy('product_id', 'unit_id')
                                    ->get()
                                    ->keyBy(function ($item) {
                                        return $item->product_id . '_' . $item->unit_id;
                                    });

                                    $details = $order->orderDetails->map(function ($detail) use ($returnedDetails) {
                                        $key = $detail->product_id . '_' . $detail->unit_id;
                                        $returnedQty = $returnedDetails->has($key) ? $returnedDetails->get($key)->total_returned : 0;
                                        
                                        $remainingQty = $detail->available_quantity - $returnedQty;
                                        
                                        if ($remainingQty <= 0) return null;

                                        return [
                                            'product_id'   => $detail->product_id,
                                            'unit_id'      => $detail->unit_id,
                                            'quantity'     => $remainingQty,
                                            'price'        => $detail->price,
                                            'package_size' => $detail->package_size ?? 1,
                                            'notes'        => 'Auto-filled from order #' . $detail->order_id,
                                        ];
                                    })->filter()->toArray();

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
                            ->minItems(1)
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
                                    ->reactive()
                                    ->afterStateUpdated(function ($set, $state, $get) {
                                        $set('unit_id', null);
                                        $set('price', 0);
                                        $set('package_size', 1);
                                        
                                        $orderId = $get('../../original_order_id');
                                        $transaction = null;
                                        
                                        if ($state && $orderId) {
                                            $transaction = \App\Models\InventoryTransaction::where('transactionable_type', Order::class)
                                                ->where('transactionable_id', $orderId)
                                                ->where('product_id', $state)
                                                ->where('movement_type', 'out')
                                                ->first();
                                        }

                                        if ($transaction) {
                                            $set('unit_id', $transaction->unit_id);
                                            $set('price', $transaction->price ?? 0);
                                            $set('package_size', $transaction->package_size ?? 1);
                                        } elseif ($state) {
                                            $firstUnitPrice = \App\Models\UnitPrice::where('product_id', $state)->first();
                                            if ($firstUnitPrice) {
                                                $set('unit_id', $firstUnitPrice->unit_id);
                                                $set('price', $firstUnitPrice->price ?? 0);
                                                $set('package_size', $firstUnitPrice->package_size);
                                            }
                                        }
                                    })
                                    ->columnSpan(2),

                                Select::make('unit_id')->columnSpan(2)
                                    ->label('Unit')
                                    ->options(function (callable $get) {
                                        $productId = $get('product_id');
                                        if (!$productId) return [];

                                        $options = \App\Models\UnitPrice::with('unit:id,name')
                                            ->where('product_id', $productId)
                                            ->get()
                                            ->pluck('unit.name', 'unit_id')
                                            ->toArray();
                                            
                                        $selectedUnitId = $get('unit_id');
                                        if ($selectedUnitId && !isset($options[$selectedUnitId])) {
                                            $unit = \App\Models\Unit::find($selectedUnitId);
                                            if ($unit) {
                                                $options[$selectedUnitId] = $unit->name;
                                            }
                                        }
                                        return $options;
                                    })
                                    ->disabled()
                                    ->dehydrated()
                                    ->required(),

                                TextInput::make('quantity')
                                    // ->extraAttributes(['class' => 'text-center'])
                                    ->extraInputAttributes(['class' => 'text-center'])
                                    ->label('Quantity')
                                    ->numeric()->live(onBlur: true)
                                    ->required()
                                    ->rules(function (callable $get, $record) {
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

                                        if (! $detail) {
                                            return [];
                                        }
                                        
                                        $returnedQtyQuery = \App\Models\ReturnedOrderDetail::whereHas('returnedOrder', function($q) use ($orderId) {
                                            $q->where('original_order_id', $orderId)
                                              ->where('status', '!=', \App\Models\ReturnedOrder::STATUS_REJECTED);
                                        })
                                        ->where('product_id', $productId)
                                        ->where('unit_id', $unitId);
                                        
                                        if ($record && $record->exists) { 
                                            $returnedQtyQuery->where('id', '!=', $record->id);
                                        }
                                        
                                        $returnedQty = $returnedQtyQuery->sum('quantity');
                                           
                                        $maxQty = $detail->available_quantity - $returnedQty;

                                        return ['max:' . max(0, $maxQty)];
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
