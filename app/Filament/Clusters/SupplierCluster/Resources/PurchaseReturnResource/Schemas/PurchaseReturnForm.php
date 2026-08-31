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
use App\Modules\Stock\PurchaseReturns\Queries\GetInvoiceReturnableItemsQuery;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Repeater\TableColumn;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class PurchaseReturnForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('General Information')
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
                                        ->pluck('invoice_no', 'id');
                                })
                                ->searchable()
                                ->live()
                                ->afterStateUpdated(function ($state,   $set) {
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
                                ->options(Supplier::pluck('name', 'id'))
                                ->searchable()
                                ->required(),

                            Select::make('store_id')
                                ->label('Store')
                                ->options(Store::where('active', 1)->pluck('name', 'id'))
                                ->searchable()
                                ->required(),

                            Select::make('payment_method_id')
                                ->label('Payment / Refund Method')
                                ->options(PaymentMethod::pluck('name', 'id'))
                                ->searchable(),
                        ]),

                        Grid::make(2)->schema([
                            TextInput::make('total_amount')
                                ->label('Total Amount')
                                ->numeric()
                                ->readOnly()
                                ->default(0),

                            FileUpload::make('attachment')
                                ->label('Attachment')
                                ->directory('purchase-returns')
                                ->downloadable(),
                        ]),

                        Textarea::make('reason')
                            ->label('Return Reason')
                            ->placeholder('Specify why items are being returned to the supplier')
                            ->columnSpanFull(),

                        Textarea::make('notes')
                            ->label('Additional Notes')
                            ->columnSpanFull(),
                    ]),

                Section::make('Return Items Details')
                    ->schema([
                        Repeater::make('details')
                            ->label('Return Line Items')
                            ->relationship('details')
                            ->defaultItems(0)
                            ->columns(8)
                            ->table([
                                TableColumn::make('Product')->width('20rem'),
                                TableColumn::make('Unit')->alignCenter()->width('10rem'),
                                TableColumn::make('Package Size')->alignCenter()->width('8rem'),
                                TableColumn::make('Return Quantity')->alignCenter()->width('10rem'),
                                TableColumn::make('Unit Price')->alignCenter()->width('10rem'),
                                TableColumn::make('Total Price')->alignCenter()->width('10rem'),
                                TableColumn::make('Notes')->width('14rem'),
                            ])
                            ->schema([
                                Hidden::make('purchase_invoice_detail_id'),

                                Select::make('product_id')
                                    ->label('Product')
                                    ->options(Product::where('active', 1)->pluck('name', 'id'))
                                    ->searchable()
                                    ->required()
                                    ->columnSpan(2),

                                Select::make('unit_id')
                                    ->label('Unit')
                                    ->options(Unit::pluck('name', 'id'))
                                    ->searchable()
                                    ->required()
                                    ->columnSpan(1),

                                TextInput::make('package_size')
                                    ->label('Package Size')
                                    ->numeric()
                                    ->default(1)
                                    ->readOnly()
                                    ->columnSpan(1),

                                TextInput::make('quantity')
                                    ->label('Quantity')
                                    ->numeric()
                                    ->minValue(0.0001)
                                    ->default(1)
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(function (  $set, $state,   $get) {
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
                                    ->afterStateUpdated(function (  $set, $state,   $get) {
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
                    ]),
            ]);
    }
}
