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
use Filament\Schemas\Components\Wizard;
use Filament\Schemas\Components\Wizard\Step;
use Filament\Support\Colors\Color;
use Filament\Support\Icons\Heroicon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class PurchaseInvoiceForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Fieldset::make()->columnSpanFull()->schema([

                    Grid::make()->columnSpanFull()->columns(3)->schema([
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
                            })
                            ->suffixIconColor('primary')
                            ->suffixIcon(Heroicon::NumberedList),
                        DatePicker::make('date')
                            ->required()
                            ->placeholder('Select date')
                            ->default(date('Y-m-d'))
                            ->format('Y-m-d')
                            ->disabledOn('edit')
                            ->format('Y-m-d'),
                       
                        Toggle::make('has_description')
                            ->label('Has Description')->inline(false)
                            ->live(),


                    ]),
                    Grid::make()->columnSpanFull()->columns(4)->schema([
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
                            ->suffixIconColor('primary')
                            ->suffixIcon(Heroicon::Banknotes)
                            ->preload(),
                        TextInput::make('total_amount')
                            ->label(__('lang.total_amount'))
                            ->numeric()
                            // ->readOnly()            // نقرأه فقط من الـ repeater
                            ->default(0)
                            ->dehydrated()
                            ->suffixIconColor('primary')
                            ->rule(function (callable $get) {
                                return function (string $attribute, $value, \Closure $fail) use ($get) {
                                    // جمع كل total_price من الريبيتر
                                    $rows = $get('units') ?? [];
                                    $sum  = collect($rows)->sum(fn($row) => (float) ($row['total_price'] ?? 0));

                                    // توحيد الدقة لأربع منازل عشرية
                                    $expected = round($sum, 4);
                                    $current  = round((float) $value, 4);

                                    if ($current !== $expected) {
                                        $fail(__(
                                            'The invoice total (:total) does not match the sum of item totals (:sum).',
                                            [
                                                'total' => $current,
                                                'sum'   => $expected,
                                            ]
                                        ));
                                    }
                                };
                            })
                            ->suffixIcon(Heroicon::Banknotes)->columnSpan(1),
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
                        ->directory('purchase-invoices')
                         ->columnSpanFull()
                        // ->acceptedFileTypes(['application/pdf'])
                        ->getUploadedFileNameForStorageUsing(function (TemporaryUploadedFile $file): string {
                            return (string) str($file->getClientOriginalName())->prepend('purchase-invoice-');
                        })
                        ->live()

                        ->afterStateUpdated(function ($state, callable $set) {
                            if (!$state) return;

                            try {
                                $service = new \App\Services\AWS\Textract\AnalyzeExpenseService();

                                if ($state instanceof \Livewire\Features\SupportFileUploads\TemporaryUploadedFile) {
                                    $file = new \Illuminate\Http\UploadedFile(
                                        $state->getRealPath(),
                                        $state->getClientOriginalName(),
                                        $state->getMimeType(),
                                        $state->getError(),
                                        true
                                    );

                                    $result = $service->analyze($file);

                                    if (!empty($result['documents'][0]['line_items'])) {

                                        if (!empty($result['documents'][0]['summary']['INVOICE_RECEIPT_ID'])) {
                                            $set('invoice_no', $result['documents'][0]['summary']['INVOICE_RECEIPT_ID']);
                                        }
                                        if (!empty($result['documents'][0]['summary']['VENDOR_ID'])) {
                                            $set('supplier_id', $result['documents'][0]['summary']['VENDOR_ID']);
                                        }
                                        if (!empty($result['documents'][0]['summary']['INVOICE_RECEIPT_DATE'])) {
                                            $date = $result['documents'][0]['summary']['INVOICE_RECEIPT_DATE'];
                                            $date = date('Y-m-d', strtotime(str_replace('/', '-', $date)));
                                            // dd($date);
                                            $set('date', $date);
                                        }
                                        $items = [];

                                        foreach ($result['documents'][0]['line_items'] as $item) {
                                            // 1) استخراج اسم الوحدة من الاستجابة
                                            $unitName = trim((string)($item['unit_name'] ?? ''));

                                            // 2) محاولة إيجاد الـ ID للوحدة (حسب الاسم، أو الرمز إن لزم)
                                            $unitId = null;
                                            if ($unitName !== '') {
                                                $unitId = \App\Models\Unit::query()
                                                    ->where('name', 'like', $unitName)           // تطابق مباشر
                                                    ->orWhere('name', 'like', "%{$unitName}%")   // تطابق جزئي
                                                    ->orWhere('code', 'like', $unitName)         // لو عندك code للوحدة
                                                    ->orWhere('code', 'like', "%{$unitName}%")
                                                    ->value('id');
                                            }

                                            // 3) بناء عنصر الريبيتر بقيم نهائية (بدون Closures)
                                            $items[] = [
                                                'product_id'              => $item['existing_product_id'] ?? null,
                                                'unit_id'                 => $unitId,                                    // ← قيمة رقمية أو null
                                                'package_size'            => (float)($item['package_size'] ?? 0),
                                                'quantity'                => (float)($item['quantity'] ?? 1),
                                                'price'                   => (float)($item['unit_price'] ?? 0),
                                                'total_price'             => (float)($item['price'] ?? 0),
                                                'waste_stock_percentage'  => 0,
                                            ];
                                        }

                                        $set('units', $items);
                                        $total = collect($items)->sum(function ($row) {
                                            return (float)($row['total_price'] ?? 0);
                                        });
                                        $set('total_amount', $total);

                                        \Filament\Notifications\Notification::make()
                                            ->title('✅ تم تحليل الفاتورة بنجاح')
                                            ->body('تم استيراد المنتجات تلقائيًا من المرفق.')
                                            ->success()
                                            ->send();
                                    } else {
                                        \Filament\Notifications\Notification::make()
                                            ->title('⚠️ لم يتم العثور على عناصر')
                                            ->body('لم يتمكن النظام من استخراج بنود من الفاتورة.')
                                            ->warning()
                                            ->send();
                                    }
                                }
                            } catch (\Throwable $e) {
                                Log::error('faild_file', [$e->getMessage()]);
                                \Filament\Notifications\Notification::make()
                                    ->title('❌ فشل تحليل الملف')
                                    ->body($e->getMessage())
                                    ->danger()
                                    ->send();
                            }
                        })

                        ->hiddenOn('view'),
                    Repeater::make('units')->columnSpanFull()->hiddenOn(['view', 'edit'])
                        ->createItemButtonLabel(__('lang.add_item'))
                        ->columns(9)
                        ->defaultItems(1)
                        ->table([
                            TableColumn::make(__('Product'))->width('24rem'),
                            TableColumn::make(__('Unit'))->alignCenter()->width('12rem'),
                            TableColumn::make(__('lang.psize'))->alignCenter()->width('8rem'),
                            TableColumn::make(__('Qty'))->alignCenter()->width('8rem'),
                            TableColumn::make(__('Price'))->alignCenter()->width('10rem'),
                            TableColumn::make(__('Total'))->alignCenter()->width('10rem'),
                            TableColumn::make(__('Waste %'))->alignCenter()->width('12rem'),
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
                                        ->whereNot('type', Product::TYPE_FINISHED_POS)
                                        ->unmanufacturingCategory()
                                        ->orderBy('id', 'asc')
                                        ->get(['id', 'code', 'name', 'active'])

                                        ->mapWithKeys(fn($product) => [
                                            $product->id => "{$product->code} - {$product->name}"
                                        ]);
                                })
                                ->getSearchResultsUsing(function (string $search): array {
                                    return Product::where('active', 1)
                                        ->whereNot('type', Product::TYPE_FINISHED_POS)

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
                                ->afterStateUpdated(function ($set, $state, callable $get) {
                                    $set('unit_id', null);
                                    $product = Product::find($state);
                                    $set('waste_stock_percentage', $product?->waste_stock_percentage);

                                    if ($product && $product->supplyUnitPrices->isNotEmpty()) {
                                        $firstUnitPrice = $product->supplyUnitPrices->first();

                                        // تعيين أول وحدة بشكل تلقائي
                                        $set('unit_id', $firstUnitPrice->unit_id);
                                        $set('price', $firstUnitPrice->price ?? 0);
                                        $set('package_size', $firstUnitPrice->package_size ?? 0);

                                        // حساب التوتال بناءً على الكمية الحالية (أو 1 افتراضياً)
                                        $quantity = (float)($get('quantity') ?? 1);
                                        $total = round($quantity * (float)($firstUnitPrice->price ?? 0), 2);
                                        $set('total_price', $total);
                                    }

                                    // تحديث إجمالي الفاتورة
                                    self::recalculateTotalAmount($set, $get);
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

                                    self::recalculateTotalAmount($set, $get);
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
                                    self::recalculateTotalAmount($set, $get);
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

                                    self::recalculateTotalAmount($set, $get);
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
                    // ->afterStateUpdated(function (Set $set, $state, callable $get) {
                    //     self::recalculateTotalAmount($set, $get);
                    // })
                ])
            ]);
    }

    /**
     * Recalculate invoice total_amount from repeater units.
     *
     * @param \Filament\Schemas\Components\Utilities\Set $set
     * @param callable $get
     * @return void
     */
    protected static function recalculateTotalAmount(Set $set, callable $get): void
    {
        $rows = $get('../../units') ?? [];

        // dd($rows);
        $total = collect($rows)->sum(function ($row) {
            return (float)($row['total_price'] ?? 0);
        });

        // dd($total);
        $total = round($total, 4);
        $set('../../total_amount', $total);
    }
}
