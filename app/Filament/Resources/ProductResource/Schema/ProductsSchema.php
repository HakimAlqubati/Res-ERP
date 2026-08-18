<?php

namespace App\Filament\Resources\ProductResource\Schema;


use App\Enums\ProductCodeGenerationMethod;
use App\Filament\Resources\ProductResource;
use App\Models\Category;
use App\Models\InventoryTransaction;
use App\Models\Product;
use App\Models\ProductItem;
use App\Models\PurchaseInvoiceDetail;
use App\Models\Setting;
use App\Models\StockIssueOrderDetail;
use App\Models\Unit;
use App\Filament\Resources\ProductResource\Support\ProductResourceActions as PRA;
use App\Models\OrderDetails;
use App\Models\UnitPrice;
use Closure;
use Illuminate\Support\Str;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Filament\Actions\Action;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Repeater\TableColumn;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\FileUpload;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Components\Wizard;
use Filament\Schemas\Components\Wizard\Step;
use Filament\Schemas\Schema;

class ProductsSchema
{

    public static function  configure(Schema $schema): Schema
    {
        return $schema->components([
            Fieldset::make()->columnSpanFull()->columns(2)->schema([
                Placeholder::make('name_above')
                    ->label(__('lang.name'))
                    ->content(fn($record) => $record?->name ?? '-')
                    ->visibleOn('edit'),
                Placeholder::make('code_above')
                    ->label(__('lang.code'))
                    ->content(fn($record) => $record?->code ?? '-')
                    ->visibleOn('edit'),
            ]),
            Wizard::make()->skippable()
                ->columnSpanFull()
                ->schema([
                    Step::make('Basic Info')
                        ->icon('heroicon-o-information-circle')
                        ->schema([
                            Grid::make(3)->schema([
                                Group::make()->columnSpan(2)->schema([
                                    Grid::make(2)->schema([
                                        TextInput::make('name')->required()->label(__('lang.name'))
                                            ->live(onBlur: true)
                                            ->unique(ignoreRecord: true),
                                        Select::make('category_id')->required()->label(__('lang.category'))
                                            ->searchable()->live()
                                            ->preload()
                                            ->options(function () {
                                                $type = request()->query('type');
                                                return Category::
                                                select('name','id','is_manafacturing')
                                                ->when($type == 'manufacturing', function ($query) use ($type) {
                                                    $query->where('is_manafacturing', true)
                                                    ;
                                                })
                                                ->notForPos()
                                                ->pluck('name', 'id');
                                            })
                                            ->afterStateUpdated(function ($set, $state) {
                                                if (ProductCodeGenerationMethod::isAuto()) {
                                                    $set('code', Product::generateProductCode($state));
                                                }
                                            }),
                                        TextInput::make('code')
                                            ->required(fn () => ProductCodeGenerationMethod::isManual())
                                            ->maxLength(fn () => ProductCodeGenerationMethod::isAuto() ? null : (int) Setting::getSetting('product_code_length', 3))
                                            ->rules(fn () => ProductCodeGenerationMethod::isManual() ? ['regex:/^[A-Z0-9]+(-[A-Z0-9]+)*$/'] : [])
                                            ->validationMessages([
                                                'regex' => __('lang.product_code_invalid_format'),
                                            ])
                                            ->extraInputAttributes(fn () => ProductCodeGenerationMethod::isManual() ? [
                                                'style' => 'text-transform: uppercase;',
                                                'oninput' => "this.value = this.value.toUpperCase().replace(/[^A-Z0-9-]/g, '').replace(/^-+/, '').replace(/-{2,}/g, '-');",
                                            ] : [])
                                            ->dehydrateStateUsing(fn ($state) => $state ? trim(preg_replace('/-+/', '-', preg_replace('/[^A-Z0-9-]/', '', strtoupper($state))), '-') : $state)
                                            ->unique(ignoreRecord: true)
                                            ->label(__('lang.code'))
                                            ->readOnly(fn () => ProductCodeGenerationMethod::isAuto())
                                            ->helperText(fn () => ProductCodeGenerationMethod::isAuto()
                                                ? __('lang.product_code_helper')
                                                : __('lang.product_code_manual_helper')
                                            )
                                            ->placeholder(fn () => ProductCodeGenerationMethod::isAuto() ? 'Code generates automatically' : 'Enter code manually')
                                            ->disabled(fn () => ProductCodeGenerationMethod::isAuto())
                                            ->dehydrated()
                                            ->default(fn ($get) => ProductCodeGenerationMethod::isAuto() ? Product::generateProductCode($get('category_id')) : null),
                                        TextInput::make('code_old_system')
                                            ->required()
                                            // ->columnSpanFull()
                                            ->unique(ignoreRecord: true)
                                            ->label(__('lang.code_old_system'))
                                            ->helperText(__('lang.code_old_system_helper'))
                                            ->placeholder(__('lang.code_old_system_placeholder'))
                                            ->visible(fn () => filter_var(Setting::getSetting('show_old_system_code'), FILTER_VALIDATE_BOOLEAN)),
                                                ]),
                                ]),
                                
                                Group::make()->columnSpan(1)->schema([
                                    FileUpload::make('image')
                                        ->label(__('lang.image'))
                                        ->image()
                                        ->disk('public')
                                        ->directory('products')
                                        ->visibility('public')
                                        ->imageEditor()
                                        ->imageEditorAspectRatios([
                                            '16:9',
                                            '4:3',
                                            '1:1',
                                        ])
                                        ->saveUploadedFileUsing(function (TemporaryUploadedFile $file): string {
                                            try {
                                                $manager = new \Intervention\Image\ImageManager(new \Intervention\Image\Drivers\Gd\Driver());
                                                $img = $manager->read($file->get());
                                                $img->scaleDown(width: 1200);
                                                $encodedImage = $img->toJpeg(70);
                                                $filename = 'products/' . Str::random(15) . '.jpeg';
                                                \Illuminate\Support\Facades\Storage::disk('public')->put($filename, (string) $encodedImage, 'public');
                                                return $filename;
                                            } catch (\Exception $e) {
                                                \Illuminate\Support\Facades\Log::error('Product Image Upload Error: ' . $e->getMessage());
                                                throw $e;
                                            }
                                        })
                                        ->maxSize(20000)
                                        ->panelAspectRatio('1:1'),
                                ]),
                            ]),
                            
                            Section::make('Stock & Additional Info')
                                ->schema([
                                    Grid::make()->columns(4)->schema([
                                        TextInput::make('sku')
                                            ->label('SKU')
                                            ->placeholder('SKU code')
                                            ->unique(ignoreRecord: true)
                                            ->maxLength(50),
                                        TextInput::make('minimum_stock_qty')->numeric()->default(0)->required()
                                            ->label(__('stock.minimum_quantity'))
                                            ->helperText(__('stock.minimum_quantity_desc')),
                                        TextInput::make('waste_stock_percentage')
                                            ->label('Waste %')
                                            ->numeric()
                                            ->minValue(0)
                                            ->default(0)
                                            ->maxValue(100),
                                        Toggle::make('active')
                                            ->inline(false)->default(true)
                                            ->label(__('lang.active')),
                                    ]),
                                    Textarea::make('description')->label(__('lang.description'))->columnSpanFull()
                                        ->rows(2),
                                ])
                                ->collapsible(),
                        ]),

                    Step::make('products')
                        ->visible(fn($get): bool => ($get('category_id') !== null && Category::find($get('category_id'))->is_manafacturing))
                        ->label('Items')
                        ->schema([
                            Repeater::make('productItems')
                                ->relationship('productItems')

                                ->table([
                                    TableColumn::make(__('Product'))->width('24rem'),
                                    TableColumn::make(__('Unit'))->alignCenter()->width('18rem'),
                                    TableColumn::make(__('Qty'))->alignCenter()->width('8rem'),
                                    TableColumn::make(__('Price'))->alignCenter()->width('10rem'),
                                    TableColumn::make(__('Total'))->alignCenter()->width('10rem'),
                                    TableColumn::make(__('Waste %'))->alignCenter()->width('8rem'),
                                    TableColumn::make(__('Net'))->alignCenter()->width('10rem'),
                                ])

                                ->label('Product Items')
                                ->schema([
                                    Hidden::make('unitPricesCache')
                                        ->dehydrated(false)
                                        ->default([]),
                                    Select::make('product_id')
                                        ->label(__('lang.product'))
                                        ->searchable()
                                        ->required()
                                        // ->disabledOn('edit')
                                        ->options(function () {
                                            return Product::where('active', 1)
                                                ->get()
                                                ->mapWithKeys(fn($product) => [
                                                    $product->id => "{$product->code} - {$product->name}",
                                                ]);
                                        })
                                        ->getSearchResultsUsing(function (string $search): array {
                                            return Product::where('active', 1)
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
                                        ->reactive()
                                        // ->afterStateUpdated(function ($set, $state) {

                                        //     $set('unit_id', null);
                                        // })
                                        ->afterStateUpdated(function (\Filament\Schemas\Components\Utilities\Set $set, $state) {
                                            $set('unit_id', null);

                                            // جهّز خريطة الأسعار للواجهة (مثال مبسّط: unit_id => ['price' => ...])
                                            $prices = \App\Models\UnitPrice::where('product_id', $state)
                                                ->get(['unit_id', 'price'])
                                                ->mapWithKeys(fn($r) => [$r->unit_id => ['price' => (float) $r->price]])
                                                ->toArray();

                                            $set('unitPricesCache', $prices);
                                        })
                                        ->searchable()->columnSpan(3),
                                    Select::make('unit_id')
                                        ->label(__('lang.unit'))
                                        ->placeholder('Select')
                                        ->required()
                                        // ->disabledOn('edit')
                                        ->options(
                                            function (callable $get) {

                                                $unitPrices = Product::find($get('product_id'))?->manufacturingUnitPrices?->toArray() ?? [];

                                                if ($unitPrices) {
                                                    return array_column($unitPrices, 'unit_name', 'unit_id');
                                                }

                                                return [];
                                            }
                                        )
                                        // ->searchable()
                                        ->reactive()

                                        ->afterStateUpdated(function (Set $set, $state, $get) {
                                            $unitPrice = UnitPrice::where(
                                                'product_id',
                                                $get('product_id')
                                            )->where('unit_id', $state)->first() ?? null;
                                            $set('price', ($unitPrice->price ?? 0));
                                            $total = ((float) ($unitPrice->price ?? 0)) * ((float) $get('quantity'));
                                            $set('total_price', $total);
                                            // if ($get('qty_waste_percentage') <= 0) {
                                            //     $set('total_price_after_waste', $total);
                                            // } else {
                                            // }
                                            // $set('total_price_after_waste', $total);
                                            $set('total_price_after_waste', ProductItem::calculateTotalPriceAfterWaste($total ?? 0, $get('qty_waste_percentage') ?? 0));
                                            // $set('package_size', $unitPrice->package_size ?? 0);
                                            $set('quantity_after_waste', ProductItem::calculateQuantityAfterWaste($get('quantity') ?? 0, $get('qty_waste_percentage') ?? 0));
                                            PRA::updateFinalPriceEachUnit($set, $get, $get('../../productItems'));
                                        })
                                        ->columnSpan(1),
                                    // TextInput::make('package_size')->numeric()->default(1)->required()
                                    // ->label(__('lang.package_size'))->readOnly(),
                                    TextInput::make('quantity')
                                        ->label(__('lang.quantity'))
                                        ->numeric()
                                        ->default(1)
                                        ->live(onBlur: true)
                                        ->afterStateUpdated(function (Set $set, $state, $get) {

                                            $currentPrice = (float) $get('price');
                                            if ($currentPrice <= 0) {
                                                $currentPrice = getUnitPrice($get('product_id'), $get('unit_id'));
                                                $set('price', $currentPrice);
                                            }
                                            $unitPrice = $currentPrice;

                                            $res = ((float) $state) * ($unitPrice);

                                            $res = round($res, 8);
                                            if ($get('qty_waste_percentage') == 0) {
                                                $set('total_price_after_waste', $res);
                                            }
                                            $set('total_price', $res);

                                            $set('total_price_after_waste', ProductItem::calculateTotalPriceAfterWaste($res ?? 0, $get('qty_waste_percentage') ?? 0));
                                            $set('quantity_after_waste', ProductItem::calculateQuantityAfterWaste($state ?? 0, $get('qty_waste_percentage') ?? 0));

                                            PRA::updateFinalPriceEachUnit($set, $get, $get('../../productItems'));
                                        })->required()->minValue(0.000000001),
                                    TextInput::make('price')
                                        ->label(__('lang.price'))
                                        ->numeric()
                                        ->default(1)
                                        ->live(onBlur: true)
                                        ->afterStateUpdated(function (Set $set, $state, $get) {
                                            $res = ((float) $state) * ((float) $get('quantity'));
                                            $res = round($res, 8);
                                            if ($get('qty_waste_percentage') == 0) {
                                                $set('total_price_after_waste', $res);
                                            }
                                            $set('total_price_after_waste', ProductItem::calculateTotalPriceAfterWaste($res, $get('qty_waste_percentage') ?? 0));
                                            $set('total_price', $res);
                                            PRA::updateFinalPriceEachUnit($set, $get, $get('../../productItems'));
                                        })->required()->minValue(0.000000001),
                                    TextInput::make('total_price')->default(0)
                                        ->type('text')
                                        ->extraInputAttributes(['readonly' => true]),
                                    TextInput::make('qty_waste_percentage')
                                        ->label('Waste %')
                                        ->default(0)
                                        // ->maxLength(2)
                                        // ->minLength(1)
                                        ->maxValue(100)
                                        ->minValue(0)
                                        ->numeric()
                                        ->required()
                                        // ->suffixIconColor(Color::Green)
                                        // ->suffixIcon('heroicon-o-percent-badge')
                                        ->live(onBlur: true)
                                        ->afterStateUpdated(function (Set $set, $state, $get) {
                                            $totalPrice = (float) $get('total_price');

                                            $res = ProductItem::calculateTotalPriceAfterWaste($totalPrice ?? 0, $state ?? 0);
                                            $res = round($res, 8);
                                            $set('total_price_after_waste', $res);
                                            $qty = $get('quantity') ?? 0;
                                            if (is_numeric($qty) && $qty > 0) {
                                                $set('quantity_after_waste', ProductItem::calculateQuantityAfterWaste($qty, $state ?? 0));
                                                PRA::updateFinalPriceEachUnit($set, $get, $get('../../productItems'));
                                            }
                                        }),

                                    TextInput::make('total_price_after_waste')->default(0)
                                        ->type('text')->label('Net Price')
                                        ->extraInputAttributes(['readonly' => true]),
                                    Hidden::make('quantity_after_waste'),
                                    // TextInput::make('quantity_after_waste')->default(0)
                                    //     ->type('text')
                                    //     ->extraInputAttributes(['readonly' => true]),
                                ])
                                ->afterStateUpdated(function (Set $set, $get) {
                                    PRA::updateFinalPriceEachUnit($set, $get, $get('productItems'), true);
                                })
                                ->columns(9)                         // Adjusts how fields are laid out in each row
                                ->createItemButtonLabel('Add Item'), // Custom button label
                            // ->minItems(1)

                        ]),
                    StepUnmanufacturingUnits::step(),
                    StepManufacturingUnits::step(),


                    self::productionDetailsStep(),
                ])
        ]);
    }

    public static function productionDetailsStep(): Step
    {
        return Step::make('production_details')
            ->label('Production Details')
            ->columnSpanFull()
            ->visible(fn($get): bool => ($get('category_id') !== null && Category::find($get('category_id'))->is_manafacturing))
            ->schema([
                Grid::make()
                    ->columnSpanFull()
                    ->schema([
                        Fieldset::make('Production Info')
                            ->columnSpanFull()
                            ->relationship('halalCertificate')
                            ->schema([
                                Grid::make()->columns(3)
                                    ->columnSpanFull()
                                    ->schema([
                                        TextInput::make('shelf_life_value')
                                            ->label(__('lang.shelf_life_value'))
                                            ->numeric()
                                            ->minValue(1),
                                        Select::make('shelf_life_unit')
                                            ->label(__('lang.shelf_life_unit'))
                                            ->options(\App\Models\ProductHalalCertificate::getShelfLifeUnitOptions())
                                            ->default('month'),

                                        TextInput::make('net_weight')
                                            ->label('Net Weight')
                                            ->placeholder('Net Weight (e.g. 500g, 1kg)'),
                                        FileUpload::make('halal_logo')
                                            ->label('Halal Logo')
                                            ->image()
                                            ->directory('halal-logos')
                                            ->visibility('public'),
                                    ]),



                                Textarea::make('allergen_info')
                                    ->label(__('lang.allergen_info'))
                                    ->placeholder('e.g. Contains allergen from soy. May contain allergen from nuts and dairy.')
                                    ->rows(3)
                                    ->columnSpanFull(),
                            ])
                    ])

            ]);
    }
}
