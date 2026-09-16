<?php

declare(strict_types=1);

namespace App\Filament\Clusters\SupplierCluster\Resources\PurchaseReturnResource\Schemas;

use App\Models\PaymentMethod;
use App\Models\Product;
use App\Models\PurchaseInvoice;
use App\Models\PurchaseReturn;
use App\Models\Store;
use App\Models\Supplier;
use App\Models\Unit;
use App\Models\UnitPrice;
use App\Modules\Stock\PurchaseReturns\Queries\GetInvoiceReturnableItemsQuery;
use App\Modules\Stock\PurchaseReturns\Rules\ProductBelongsToInvoiceRule;
use App\Modules\Stock\PurchaseReturns\Rules\ReturnQuantityWithinLimitRule;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Repeater\TableColumn;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Wizard;
use Filament\Schemas\Components\Wizard\Step;
use Filament\Schemas\Schema;

class PurchaseReturnForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Wizard::make([
                    // Step 1: General Information
                    Step::make('General Information')
                    
                        ->icon('heroicon-o-information-circle')
                        ->schema([
                            Grid::make(3)->schema([
                                TextInput::make('return_no')
                                    ->label('Return Number')
                                    ->default(fn() => PurchaseReturn::autoReturnNo())
                                    ->readOnly()
                                    ->required(),

                                DatePicker::make('return_date')
                                    ->label('Return Date')
                                    ->default(date('Y-m-d'))
                                    ->required(),

                                Select::make('purchase_invoice_id')
                                    ->label('Original Purchase Invoice')
                                    ->options(function () {
                                        return PurchaseInvoice::query()
                                            ->where('cancelled', false)
                                            ->orderBy('id', 'desc')
                                            ->limit(100)
                                            ->get(['id', 'invoice_no'])
                                            ->mapWithKeys(fn($inv) => [
                                                $inv->id => ! empty($inv->invoice_no) ? "{$inv->invoice_no} (#{$inv->id})" : "Invoice #{$inv->id}",
                                            ])
                                            ->toArray();
                                    })
                                    ->searchable()
                                    ->live()
                                    ->afterStateUpdated(function ($state, $set) {
                                        if ($state) {
                                            $query = app(GetInvoiceReturnableItemsQuery::class);
                                            $data = $query->execute((int) $state);

                                            $set('supplier_id', $data['supplier_id']);
                                            $set('store_id', $data['store_id']);
                                            $set('details', $data['items']);

                                            $total = collect($data['items'])->sum(fn($row) => (float) ($row['total_price'] ?? 0));
                                            $set('total_amount', round($total, 4));
                                        }
                                    }),
                            ]),

                            Grid::make(3)->schema([
                                Select::make('supplier_id')
                                    ->label('Supplier')
                                    ->options(function () {
                                        return Supplier::pluck('name', 'id')
                                            ->mapWithKeys(fn($name, $id) => [$id => (string) ($name ?? "Supplier #{$id}")])
                                            ->toArray();
                                    })
                                    ->searchable()
                                    ->required(),

                                Select::make('store_id')
                                    ->label('Store')
                                    ->options(function () {
                                        return Store::where('active', 1)
                                            ->pluck('name', 'id')
                                            ->mapWithKeys(fn($name, $id) => [$id => (string) ($name ?? "Store #{$id}")])
                                            ->toArray();
                                    })
                                    ->searchable()
                                    ->required(),

                                Select::make('payment_method_id')
                                    ->label('Payment / Refund Method')
                                    ->options(function () {
                                        return PaymentMethod::pluck('name', 'id')
                                            ->mapWithKeys(fn($name, $id) => [$id => (string) ($name ?? "Payment Method #{$id}")])
                                            ->toArray();
                                    })
                                    ->searchable(),
                                    
                                Hidden::make('total_amount')
                                    ->default(0),
                            ]),

                            FileUpload::make('attachment')
                                ->label('Attachment / Invoice Copy')
                                ->directory('purchase_returns')
                                ->disk('public')
                                ->image()
                                ->columnSpanFull(),

                            Textarea::make('reason')
                                ->label('Return Reason')
                                ->placeholder('Specify why items are being returned to the supplier')
                                ->columnSpanFull(),

                            Textarea::make('notes')
                                ->label('Additional Notes')
                                ->columnSpanFull(),
                        ]),

                    // Step 2: Return Items Details
                    Step::make('Return Items Details')
                        ->icon('heroicon-o-queue-list')
                        ->schema([
                            Repeater::make('details')
                                ->label('Return Line Items')
                                ->defaultItems(0)
                                ->minItems(1)
                                ->required()
                                ->validationMessages([
                                    'min' => 'Please add at least one item to return.',
                                ])
                                ->columns(8)
                                ->table([
                                    TableColumn::make('Product')->width('18rem'),
                                    TableColumn::make('Unit')->alignCenter()->width('8rem'),
                                    TableColumn::make('Package Size')->alignCenter()->width('7rem'),
                                    TableColumn::make('Purchased Qty')->alignCenter()->width('8rem'),
                                    TableColumn::make('Return Qty')->alignCenter()->width('8rem'),
                                    TableColumn::make('Unit Price')->alignCenter()->width('8rem'),
                                    TableColumn::make('Total Price')->alignCenter()->width('8rem'),
                                    TableColumn::make('Notes')->width('12rem'),
                                ])
                                ->schema([
                                    Hidden::make('purchase_invoice_detail_id'),

                                    Select::make('product_id')
                                        ->label('Product')
                                        ->options(function ($get) {
                                            $invoiceId = $get('../../purchase_invoice_id');
                                            if ($invoiceId) {
                                                $invoice = PurchaseInvoice::with('purchaseInvoiceDetails.product')->find($invoiceId);
                                                if ($invoice && $invoice->purchaseInvoiceDetails->isNotEmpty()) {
                                                    return $invoice->purchaseInvoiceDetails
                                                        ->mapWithKeys(fn($d) => [
                                                            $d->product_id => (string) ($d->product?->code ? "{$d->product->code} - {$d->product->name}" : ($d->product?->name ?? "Product #{$d->product_id}")),
                                                        ])
                                                        ->toArray();
                                                }
                                            }

                                            return Product::where('active', 1)
                                                ->get(['id', 'name', 'code'])
                                                ->mapWithKeys(fn($p) => [$p->id => (string) ($p->code ? "{$p->code} - {$p->name}" : ($p->name ?? "Product #{$p->id}"))])
                                                ->toArray();
                                        })
                                        ->searchable()
                                        ->live()
                                        ->rules([
                                            fn($get): ProductBelongsToInvoiceRule => new ProductBelongsToInvoiceRule(
                                                purchaseInvoiceId: (int) $get('../../purchase_invoice_id') ?: null
                                            ),
                                        ])
                                        ->afterStateUpdated(function ($set, $state, $get) {
                                            $invoiceId = $get('../../purchase_invoice_id');
                                            if ($invoiceId && $state) {
                                                $detail = \App\Models\PurchaseInvoiceDetail::where('purchase_invoice_id', $invoiceId)
                                                    ->where('product_id', $state)
                                                    ->first();
                                                if ($detail) {
                                                    $set('purchase_invoice_detail_id', $detail->id);
                                                    $set('unit_id', $detail->unit_id);
                                                    $set('package_size', $detail->package_size);
                                                    $set('unit_price', $detail->price);
                                                    $set('purchased_quantity', $detail->quantity);

                                                    $qty = (float) ($get('quantity') ?? 1);
                                                    $set('total_price', round($qty * (float) $detail->price, 4));

                                                    $rows = $get('../../details') ?? [];
                                                    $sum = collect($rows)->sum(fn($r) => (float) ($r['total_price'] ?? 0));
                                                    $set('../../total_amount', round($sum, 4));
                                                    return;
                                                }
                                            }

                                            if ($state) {
                                                $product = Product::with('unitPrices')->find($state);
                                                $firstUnitPrice = $product?->unitPrices?->first();
                                                $set('purchase_invoice_detail_id', null);
                                                $set('unit_id', $firstUnitPrice?->unit_id);
                                                $set('package_size', $firstUnitPrice?->package_size ?? 1);
                                                $set('unit_price', $firstUnitPrice?->price ?? 0);
                                                $set('purchased_quantity', null);

                                                $qty = (float) ($get('quantity') ?? 1);
                                                $set('total_price', round($qty * (float) ($firstUnitPrice?->price ?? 0), 4));

                                                $rows = $get('../../details') ?? [];
                                                $sum = collect($rows)->sum(fn($r) => (float) ($r['total_price'] ?? 0));
                                                $set('../../total_amount', round($sum, 4));
                                            } else {
                                                $set('purchase_invoice_detail_id', null);
                                                $set('unit_id', null);
                                                $set('package_size', 1);
                                                $set('unit_price', 0);
                                                $set('purchased_quantity', null);
                                                $set('total_price', 0);
                                            }
                                        })
                                        ->required()
                                        ->columnSpan(2),

                                    Select::make('unit_id')
                                        ->label('Unit')
                                        ->options(function ($get) {
                                            $productId = $get('product_id');
                                            if (! $productId) {
                                                return [];
                                            }

                                            $product = Product::with('unitPrices.unit')->find($productId);
                                            if (! $product) {
                                                return [];
                                            }

                                            $options = $product->unitPrices
                                                ->filter(fn($up) => $up->unit !== null)
                                                ->mapWithKeys(fn($up) => [
                                                    $up->unit_id => (string) ($up->unit->name ?? "Unit #{$up->unit_id}"),
                                                ])
                                                ->toArray();

                                            $selectedUnitId = $get('unit_id');
                                            if ($selectedUnitId && ! isset($options[$selectedUnitId])) {
                                                $unit = Unit::find($selectedUnitId);
                                                if ($unit) {
                                                    $options[$selectedUnitId] = (string) ($unit->name ?? "Unit #{$selectedUnitId}");
                                                }
                                            }

                                            return $options;
                                        })
                                        ->searchable(false)
                                        ->live()
                                        ->afterStateUpdated(function ($state, $set, $get) {
                                            $productId = $get('product_id');
                                            if (! $productId || ! $state) {
                                                return;
                                            }

                                            $unitPrice = UnitPrice::where('product_id', $productId)
                                                ->where('unit_id', $state)
                                                ->first();

                                            $newPackageSize = max(1.0, (float) ($unitPrice?->package_size ?? 1.0));
                                            $set('package_size', $newPackageSize);

                                            $detailId = $get('purchase_invoice_detail_id');
                                            $invoiceId = $get('../../purchase_invoice_id');
                                            $detail = null;

                                            if ($detailId) {
                                                $detail = \App\Models\PurchaseInvoiceDetail::find($detailId);
                                            } elseif ($invoiceId) {
                                                $detail = \App\Models\PurchaseInvoiceDetail::where('purchase_invoice_id', $invoiceId)
                                                    ->where('product_id', $productId)
                                                    ->first();
                                                if ($detail) {
                                                    $set('purchase_invoice_detail_id', $detail->id);
                                                }
                                            }

                                            if ($detail) {
                                                $invPackageSize = max(1.0, (float) ($detail->package_size ?? 1.0));
                                                $convertedPurchasedQty = round(((float) $detail->quantity * $invPackageSize) / $newPackageSize, 4);

                                                if ((float) $detail->price > 0) {
                                                    $newUnitPrice = round(((float) $detail->price / $invPackageSize) * $newPackageSize, 4);
                                                } else {
                                                    $newUnitPrice = (float) ($unitPrice?->price ?? 0);
                                                }

                                                $set('purchased_quantity', $convertedPurchasedQty);
                                                $set('unit_price', $newUnitPrice);
                                            } else {
                                                $newUnitPrice = (float) ($unitPrice?->price ?? 0);
                                                $set('purchased_quantity', null);
                                                $set('unit_price', $newUnitPrice);
                                            }

                                            $qty = (float) ($get('quantity') ?? 1);
                                            $set('total_price', round($qty * $newUnitPrice, 4));

                                            $rows = $get('../../details') ?? [];
                                            $sum = collect($rows)->sum(fn($r) => (float) ($r['total_price'] ?? 0));
                                            $set('../../total_amount', round($sum, 4));
                                        })
                                        ->required()
                                        ->columnSpan(1),

                                    TextInput::make('package_size')
                                        ->label('Package Size')
                                        ->numeric()
                                        ->default(1)
                                        ->readOnly()
                                        ->columnSpan(1),

                                    TextInput::make('purchased_quantity')
                                        ->label('Purchased Qty')
                                        ->numeric()
                                        ->readOnly()
                                        ->dehydrated(false)
                                        ->formatStateUsing(function ($state, $record, $get) {
                                            $detailId = $get('purchase_invoice_detail_id');
                                            $invoiceId = $get('../../purchase_invoice_id');
                                            $productId = $get('product_id');

                                            $detail = null;
                                            if ($detailId) {
                                                $detail = \App\Models\PurchaseInvoiceDetail::find($detailId);
                                            } elseif ($invoiceId && $productId) {
                                                $detail = \App\Models\PurchaseInvoiceDetail::where('purchase_invoice_id', $invoiceId)
                                                    ->where('product_id', $productId)
                                                    ->first();
                                            }

                                            if ($detail) {
                                                $invPackageSize = max(1.0, (float) ($detail->package_size ?? 1.0));
                                                $curPackageSize = max(1.0, (float) ($get('package_size') ?? 1.0));
                                                return round(((float) $detail->quantity * $invPackageSize) / $curPackageSize, 4);
                                            }

                                            if ($state !== null && $state !== '') {
                                                return $state;
                                            }

                                            return '-';
                                        })
                                        ->columnSpan(1),

                                    TextInput::make('quantity')
                                        ->label('Quantity')
                                        ->numeric()
                                        ->minValue(0)
                                        ->default(1)
                                        ->live(onBlur: true)
                                        ->rules([
                                            fn($get): ReturnQuantityWithinLimitRule => new ReturnQuantityWithinLimitRule(
                                                purchaseInvoiceId: (int) $get('../../purchase_invoice_id') ?: null,
                                                productId: (int) $get('product_id') ?: null,
                                                purchaseInvoiceDetailId: (int) $get('purchase_invoice_detail_id') ?: null,
                                                packageSize: (float) ($get('package_size') ?? 1.0),
                                            ),
                                        ])
                                        ->afterStateUpdated(function ($set, $state, $get) {
                                            $qty = (float) $state;
                                            $price = (float) ($get('unit_price') ?? 0);
                                            $total = round($qty * $price, 4);
                                            $set('total_price', $total);

                                            $rows = $get('../../details') ?? [];
                                            $sum = collect($rows)->sum(fn($r) => (float) ($r['total_price'] ?? 0));
                                            $set('../../total_amount', round($sum, 4));
                                        })
                                        ->required()
                                        ->columnSpan(1),

                                    TextInput::make('unit_price')
                                        ->label('Unit Price')
                                        ->numeric()
                                        ->live(onBlur: true)
                                        ->afterStateUpdated(function ($set, $state, $get) {
                                            $price = (float) $state;
                                            $qty = (float) ($get('quantity') ?? 0);
                                            $total = round($qty * $price, 4);
                                            $set('total_price', $total);

                                            $rows = $get('../../details') ?? [];
                                            $sum = collect($rows)->sum(fn($r) => (float) ($r['total_price'] ?? 0));
                                            $set('../../total_amount', round($sum, 4));
                                        })
                                        ->required()
                                        ->columnSpan(1),

                                    TextInput::make('total_price')
                                        ->label('Total')
                                        ->numeric()
                                        ->readOnly()
                                        ->columnSpan(1),

                                    TextInput::make('notes')
                                        ->label('Notes')
                                        ->columnSpan(1),
                                ]),
                        ])
                        ->visible(fn($get) => $get('purchase_invoice_id'))
                        ,
                ])->columnSpanFull()->skippable()
                
                ,
            ]);
    }
}
