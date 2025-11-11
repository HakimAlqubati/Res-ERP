<?php

namespace App\Filament\Resources\PurchaseInvoiceResource\Schemas;

use Filament\Pages\Enums\SubNavigationPosition;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Utilities\Set;
use App\Models\Product;
use App\Models\Store;
use App\Models\Supplier;
use App\Models\UnitPrice;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Repeater\TableColumn;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;


class PurchaseInvoiceForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Fieldset::make()->columnSpanFull()->schema([
                    Grid::make()->columnSpanFull()->columns(4)->schema([
                        TextInput::make('invoice_no')
                            ->label(__('lang.invoice_no'))
                            ->required(fn(): bool => settingWithDefault('purchase_invoice_no_required_and_disabled_on_edit', false))
                            ->unique(ignoreRecord: true)
                            // ->default(fn(): int => PurchaseInvoice::autoInvoiceNo())
                            ->placeholder('Enter invoice number')
                            ->disabled(function ($record) {
                                $setting = settingWithDefault('purchase_invoice_no_required_and_disabled_on_edit', false);
                                if ($record && $setting) {
                                    return true;
                                }
                                return false;
                            }),
                        DatePicker::make('date')
                            ->required()
                            ->placeholder('Select date')
                            ->default(date('Y-m-d'))
                            ->format('Y-m-d')
                            ->disabledOn('edit')
                            ->format('Y-m-d'),
                        Toggle::make('has_attachment')
                            ->label('Has Attachment')
                            ->inline(false)->live(),
                        Toggle::make('has_description')
                            ->label('Has Description')->inline(false)
                            ->live(),


                    ]),
                    Grid::make()->columnSpanFull()->columns(3)->schema([
                        Select::make('supplier_id')->label(__('lang.supplier'))
                            ->getSearchResultsUsing(fn(string $search): array => Supplier::where('name', 'like', "%{$search}%")->limit(10)->pluck('name', 'id')->toArray())
                            ->getOptionLabelUsing(fn($value): ?string => Supplier::find($value)?->name)
                            ->searchable()
                            ->options(Supplier::limit(5)->get(['id', 'name'])->pluck('name', 'id'))
                            ->disabledOn('edit'),

                        Select::make('store_id')->label(__('lang.store'))
                            ->searchable()
                            ->disabledOn('edit')->required()
                            ->default(getDefaultStore())
                            ->options(
                                Store::where('active', 1)
                                    ->withManagedStores()
                                    ->get(['id', 'name'])->pluck('name', 'id')
                            )
                            ->disabledOn('edit')
                            ->searchable(),
                        Select::make('payment_method_id')
                            ->label('Payment Method')
                            ->relationship('paymentMethod', 'name')
                            ->searchable()
                            ->preload()
                    ]),
                    Textarea::make('cancel_reason')->label('Cancel Reason')
                        ->placeholder('Cancel Reason')->hiddenOn('create')
                        ->visible(fn($record): bool => $record->cancelled)->readOnly()
                        ->columnSpanFull(),
                    Textarea::make('description')->label(__('lang.description'))
                        ->placeholder('Enter description')->visible(fn($get): bool => $get('has_description'))
                        ->columnSpanFull(),
                    FileUpload::make('attachment')
                        ->label(__('lang.attachment'))
                        // ->enableOpen()
                        // ->enableDownload()
                        ->directory('purchase-invoices')->visible(fn($get): bool => $get('has_attachment'))
                        ->columnSpanFull()
                        ->acceptedFileTypes(['application/pdf'])
                        ->getUploadedFileNameForStorageUsing(function (TemporaryUploadedFile $file): string {
                            return (string) str($file->getClientOriginalName())->prepend('purchase-invoice-');
                        })->hiddenOn('view'),
                    Repeater::make('units')->columnSpanFull()->hiddenOn(['view', 'edit'])
                        ->createItemButtonLabel(__('lang.add_item'))
                        ->columns(9)
                        ->defaultItems(1)
                        ->table([
                            TableColumn::make(__('Product'))->width('24rem'),
                            TableColumn::make(__('Unit'))->alignCenter()->width('18rem'),
                            TableColumn::make(__('lang.psize'))->alignCenter()->width('8rem'),
                            TableColumn::make(__('Qty'))->alignCenter()->width('8rem'),
                            TableColumn::make(__('Price'))->alignCenter()->width('10rem'),
                            TableColumn::make(__('Total'))->alignCenter()->width('10rem'),
                            TableColumn::make(__('Waste %'))->alignCenter()->width('8rem'),
                        ])

                        ->deletable(function ($record) {
                            if (is_null($record)) {
                                return true;
                            }
                            return false;
                        })
                        ->columnSpanFull()
                        ->collapsible()
                        ->relationship('purchaseInvoiceDetails')
                        ->label(__('lang.purchase_invoice_details'))
                        ->schema([
                            Select::make('product_id')
                                ->label(__('lang.product'))
                                ->searchable()
                                ->distinct()
                                ->disabledOn('edit')
                                ->options(function () {
                                    return Product::where('active', 1)
                                        ->unmanufacturingCategory()
                                        ->orderBy('id', 'asc')
                                        ->get(['id', 'code', 'name', 'active'])

                                        ->mapWithKeys(fn($product) => [
                                            $product->id => "{$product->code} - {$product->name}"
                                        ]);
                                })
                                ->getSearchResultsUsing(function (string $search): array {
                                    return Product::where('active', 1)
                                        ->where(function ($query) use ($search) {
                                            $query->where('name', 'like', "%{$search}%")
                                                ->orWhere('code', 'like', "%{$search}%");
                                        })->unmanufacturingCategory()
                                        ->limit(50)
                                        ->get()
                                        ->mapWithKeys(fn($product) => [
                                            $product->id => "{$product->code} - {$product->name}"
                                        ])
                                        ->toArray();
                                })
                                ->getOptionLabelUsing(fn($value): ?string => Product::unmanufacturingCategory()->find($value)?->code . ' - ' . Product::find($value)?->name)
                                ->reactive()
                                ->afterStateUpdated(function ($set, $state) {
                                    $set('unit_id', null);
                                    $product = Product::find($state);
                                    $set('waste_stock_percentage', $product?->waste_stock_percentage);
                                })
                                ->searchable()->columnSpan(2)
                                ->required(),
                            Select::make('unit_id')
                                ->label(__('lang.unit'))
                                ->disabledOn('edit')
                                ->options(function (callable $get) {
                                    $product = Product::find($get('product_id'));
                                    if (! $product) return [];

                                    return $product->supplyUnitPrices
                                        ->pluck('unit.name', 'unit_id')?->toArray() ?? [];
                                })
                                ->searchable()
                                ->reactive()
                                ->afterStateUpdated(function (Set $set, $state, $get) {
                                    $unitPrice = UnitPrice::where(
                                        'product_id',
                                        $get('product_id')
                                    )
                                        ->where('unit_id', $state)->first();
                                    $set('price', $unitPrice->price ?? 0);
                                    $total = round(((float) ($unitPrice->price ?? 0)) * ((float) $get('quantity')), 2) ?? 0;

                                    $set('total_price', $total ?? 0);
                                    $set('package_size',  $unitPrice->package_size ?? 0);
                                })->columnSpan(2)->required(),
                            TextInput::make('package_size')->type('number')->readOnly()->columnSpan(1)
                                ->label(__('lang.package_size')),
                            TextInput::make('quantity')
                                ->label(__('lang.quantity'))

                                ->numeric()

                                ->minValue(0.1)
                                ->default(1)
                                ->disabledOn('edit')
                                ->live(onBlur: true)
                                ->afterStateUpdated(function (Set $set, $state, $get) {
                                    $total = round(((float) $state) * ((float)$get('price') ?? 0), 2);
                                    $set('total_price', $total);
                                })->columnSpan(1)->required(),
                            TextInput::make('price')
                                ->label(__('lang.price'))
                                ->type('text')
                                ->minValue(1)
                                // ->integer()
                                ->disabledOn('edit')
                                ->live(onBlur: true)

                                ->afterStateUpdated(function (Set $set, $state, $get) {
                                    $total = round(((float) $state) * ((float)$get('quantity')), 2);
                                    $set('total_price', $total);
                                })->columnSpan(1)->required(),
                            TextInput::make('total_price')->minValue(1)->label('Total Price')
                                ->numeric()
                                ->extraInputAttributes(['readonly' => true])->columnSpan(1),
                            TextInput::make('waste_stock_percentage')
                                ->label(__('lang.waste_stock_percentage'))
                                ->suffix('%')
                                ->type('number')
                                ->minValue(0)
                                ->maxValue(100)
                                ->default(function (callable $get) {
                                    $product = Product::find($get('product_id'));
                                    return $product?->waste_stock_percentage ?? 0;
                                })
                                ->live(onBlur: true)
                                ->columnSpan(1)
                                ->required(),


                        ])
                ])
            ]);
    }
}
