<?php

namespace App\Filament\Resources\ProductResource\Schema;


use App\Filament\Resources\ProductResource;
use App\Models\Category;
use App\Models\InventoryTransaction;
use App\Models\Product;
use App\Models\ProductItem;
use App\Models\PurchaseInvoiceDetail;
use App\Models\StockIssueOrderDetail;
use App\Models\Unit;
use App\Filament\Resources\ProductResource\Support\ProductResourceActions as PRA;
use App\Models\OrderDetails;
use App\Models\UnitPrice;
use Closure;
use Filament\Actions\Action;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Repeater\TableColumn;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Grid;
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
                    Step::make('')
                        ->columns(3)
                        ->schema([
                            TextInput::make('name')->required()->label(__('lang.name'))
                                ->live(onBlur: true)
                                ->unique(ignoreRecord: true),
                            Select::make('category_id')->required()->label(__('lang.category'))
                                ->searchable()->live()
                                ->options(function () {
                                    $type = request()->query('type');
                                    return Category::when($type == 'manufacturing', function ($query) use ($type) {

                                        $query->where('is_manafacturing', true);
                                    })->pluck('name', 'id');
                                })
                                ->afterStateUpdated(function ($set, $state) {
                                    $set('code', Product::generateProductCode($state));
                                }),
                            TextInput::make('code')->required()
                                ->unique(ignoreRecord: true)
                                ->label(__('lang.code'))
                                ->readOnly()
                                ->helperText(__('lang.product_code_helper'))
                                ->placeholder('Code generates automatically')
                                ->disabled()
                                ->dehydrated()
                                ->default(fn($get) => Product::generateProductCode($get('category_id'))),
                            Grid::make()->columns(4)->columnSpanFull()->schema([
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

                    Step::make('units')->label('Units')
                        ->visible(fn($get): bool => ($get('category_id') !== null && ! Category::find($get('category_id'))->is_manafacturing))
                        ->schema([

                            Repeater::make('units')->label(__('lang.units_prices'))
                                ->columns(5)
                                // ->hiddenOn(Pages\EditProduct::class)

                                ->columnSpanFull()->minItems(1)
                                ->collapsible()->defaultItems(0)
                                ->relationship('allUnitPrices')
                                ->table([
                                    TableColumn::make(__('lang.unit'))->alignCenter()->width('14rem'),
                                    TableColumn::make(__('lang.price'))->alignCenter()->width('18rem'),
                                    TableColumn::make(__('lang.psize'))->alignCenter()->width('10rem'),
                                    TableColumn::make(__('Usage'))->alignCenter()->width('12rem'),
                                ])
                                ->rules(function (Get $get, callable $livewire) {
                                    return [
                                        function (string $attribute, $value, Closure $fail) use ($get) {
                                            $units = $get('units') ?? [];

                                            // validation مع رسالة رسمية
                                            PRA::validateUnitsPackageSizeOrder($units, $fail);
                                        },
                                    ];
                                })
                                ->deleteAction(function (Action $action) {
                                    $action->before(function (array $arguments, Repeater $component, $record) {
                                        $unitPriceRecordId = null;
                                        if (str_starts_with($arguments['item'], 'record-')) {
                                            $unitPriceRecordId = str_replace('record-', '', $arguments['item']);
                                        }

                                        if ($unitPriceRecordId) {
                                            PRA::validateUnitDeletion($unitPriceRecordId, $record);
                                        }
                                    });
                                })
                                ->orderable('product_id')
                                ->schema([
                                    Select::make('unit_id')->required()
                                        ->label(__('lang.unit'))
                                        // ->searchable()
                                        ->distinct()
                                        ->options(function () {
                                            return Unit::pluck('name', 'id');
                                        })
                                        // ->searchable()
                                        ->disabled(function (callable $get, $livewire, $record) {
                                            $isNew = is_null($get('id'));
                                            if ($isNew) {
                                                return false;
                                            }
                                            return PRA::isProductLocked($livewire->form->getRecord(), $record);
                                        }),
                                    TextInput::make('price')->numeric()->default(1)->required()
                                        ->label(__('lang.price'))
                                        ->disabled(function (callable $get, $livewire, $record) {
                                            $isNew = is_null($get('id'));
                                            if ($isNew) {
                                                return false;
                                            }
                                            return PRA::isProductLocked($livewire->form->getRecord(), $record) || $get('usage_scope') == UnitPrice::USAGE_NONE;;
                                        })
                                        ->live(onBlur: true)

                                        ->afterStateHydrated(function (Set $set, Get $get) {
                                            $units = $get('../../units') ?? [];

                                            // نحاول نجيب بيانات هذا الصف الحالي
                                            $currentPackageSize = $get('package_size') ?? null;
                                            $currentUnitId      = $get('unit_id') ?? null;

                                            // نبحث عن ترتيب هذا الصف
                                            $index = null;
                                            foreach ($units as $i => $unit) {
                                                if (($unit['unit_id'] ?? null) === $currentUnitId) {
                                                    $index = $i;
                                                    break;
                                                }
                                            }

                                            // لو أول صف أو فشل الترتيب نتركه
                                            if ($index === 0 || is_null($index)) {
                                                return;
                                            }

                                            $firstPrice = $units[0]['price'] ?? null;

                                            if ($firstPrice && $currentPackageSize && $currentPackageSize != 0) {
                                                $set('price', round($firstPrice / $currentPackageSize, 8));
                                            }
                                        })

                                        ->afterStateUpdated(function (Set $set, $state, $get) {
                                            $units = $get('../../units') ?? [];
                                            if (count($units) < 2) {
                                                return; // لازم يكون فيه أكثر من وحدة عشان نوزع الأسعار
                                            }
                                            $unitsArray = array_values($units);
                                            $firstUnit  = $unitsArray[0] ?? null;
                                            if (! $firstUnit) {
                                                return;
                                            }

                                            $firstPackageSize = $firstUnit['package_size'] ?? null;
                                            $firstPrice       = $firstUnit['price'] ?? null;

                                            if (! $firstPackageSize || ! $firstPrice) {
                                                return;
                                            }

                                            $newUnits = [];

                                            foreach ($unitsArray as $index => $unit) {
                                                if ($index === 0) {
                                                    $newUnits[] = $unit; // أول وحدة السعر ثابت (الي عدله المستخدم)
                                                    continue;
                                                }

                                                $currentPackageSize = $unit['package_size'] ?? 1;

                                                // 🧮 الحساب:
                                                $newPrice = round($firstPrice * ($currentPackageSize / $firstPackageSize), 8);

                                                $newUnits[] = array_merge($unit, [
                                                    'price' => $newPrice,
                                                ]);
                                            }

                                            // لأننا استخدمنا array_values فالمفاتيح تغيرت، نحولهم بنفس المفاتيح القديمة
                                            $originalKeys = array_keys($units);
                                            $updatedUnits = array_combine($originalKeys, $newUnits);

                                            $set('../../units', $updatedUnits);
                                        })->minValue(0),
                                    TextInput::make('package_size')

                                        ->numeric()->default(0)->required()->minValue(0)
                                        // ->maxLength(4)
                                        ->label(__('lang.package_size'))
                                        ->live(onBlur: true)
                                        ->rules(function (Get $get, callable $livewire) {
                                            return [
                                                function (string $attribute, $value, Closure $fail) use ($get, $livewire) {
                                                    $productId = $livewire->form->getRecord()?->id ?? null;
                                                    $unitId    = $get('unit_id');
                                                    $record    = $livewire->form->getRecord();

                                                    PRA::validatePackageSizeChange($productId, $unitId, $value, $fail, $record);
                                                },
                                            ];
                                        })
                                        ->afterStateUpdated(function (Set $set, $state, $get) {
                                            $allUnits   = $get('../../units') ?? [];
                                            $thisUnitId = $get('unit_id');

                                            $firstKey  = array_key_first($allUnits);
                                            $firstUnit = $allUnits[$firstKey] ?? null;

                                            $isCurrentFirst = ($firstUnit['unit_id'] ?? null) == $thisUnitId;

                                            if ($isCurrentFirst || empty($firstUnit)) {
                                                return; // لا نعدل السعر للصف الأول
                                            }

                                            $firstPrice       = $firstUnit['price'] ?? null;
                                            $firstPackageSize = $firstUnit['package_size'] ?? null;

                                            if ($firstPrice && $state != 0) {
                                                $set('price', round(($firstPrice / $firstPackageSize) * $state, 8));
                                            }
                                        })->disabled(function (callable $get, $livewire, $record) {
                                            $isNew = is_null($get('id'));
                                            if ($isNew) {
                                                return false;
                                            }
                                            return PRA::isProductLocked($livewire->form->getRecord(), $record) || $get('usage_scope') == UnitPrice::USAGE_NONE;;
                                        }),
                                    Select::make('usage_scope')
                                        ->label('Usage')
                                        ->options(UnitPrice::USAGE_SCOPES)
                                        ->default(UnitPrice::USAGE_ALL)
                                        ->disableOptionWhen(function (string $value, callable $get, $record, $livewire) {
                                            return PRA::shouldDisableUsageScopeOption(
                                                $value,
                                                $record,
                                                $livewire->form->getRecord()
                                            );
                                        })

                                        ->dehydrated()
                                        ->required()
                                        ->columnSpan(2)
                                    // ->native(false)
                                    ,

                                ])
                                ->orderColumn('order')
                                ->reorderable()
                                ->helperText(function (callable $get, $livewire, $record) {
                                    if (PRA::isProductLocked($livewire->form->getRecord(), $record)) {
                                        return '⚠️ You cannot edit units because this product has related transactions.' . "\n" . 'However, you are allowed to add new units that will be used for manufacturing';
                                    }
                                    return 'Please add units in order from largest to smallest.';
                                }),

                        ]),
                    Step::make('manafacturingProductunits')->label('Units')
                        ->visible(fn($get): bool => ($get('category_id') !== null && Category::find($get('category_id'))->is_manafacturing))
                        ->schema([

                            Repeater::make('units')->label(__('lang.units_prices'))
                                ->columns(4)
                                // ->hiddenOn(Pages\EditProduct::class)
                                ->helperText(function (callable $get, $livewire, $record) {
                                    if (PRA::isProductLocked($livewire->form->getRecord(), $record)) {
                                        return '⚠️ You cannot edit units because this product has related transactions.' . "\n" . 'However, you are allowed to add new units that will be used for manufacturing';
                                    }
                                    return 'Please add units in order from largest to smallest.';
                                })
                                ->table([
                                    TableColumn::make(__('Unit'))->alignCenter()->width('14rem'),
                                    TableColumn::make(__('lang.package_size'))->alignCenter()->width('10rem'),
                                    TableColumn::make(__('Price'))->alignCenter()->width('10rem'),
                                    TableColumn::make(__('Selling'))->alignCenter()->width('12rem'),
                                ])

                                ->columnSpanFull()->minItems(1)
                                ->collapsible()->defaultItems(0)
                                ->relationship('allUnitPrices')
                                ->deleteAction(function (Action $action) {
                                    $action->before(function (array $arguments, Repeater $component, $record) {
                                        $unitPriceRecordId = null;
                                        if (str_starts_with($arguments['item'], 'record-')) {
                                            $unitPriceRecordId = str_replace('record-', '', $arguments['item']);
                                        }

                                        if ($unitPriceRecordId) {
                                            PRA::validateUnitDeletion($unitPriceRecordId, $record);
                                        }
                                    });
                                })
                                ->rules(function (Get $get, callable $livewire) {
                                    return [
                                        function (string $attribute, $value, Closure $fail) use ($get) {
                                            $units = $get('units') ?? [];

                                            // validation مع رسالة رسمية
                                            PRA::validateUnitsPackageSizeOrder($units, $fail);
                                        },
                                    ];
                                })

                                ->orderable('product_id')
                                ->schema([
                                    Select::make('unit_id')->required()
                                        ->label(__('lang.unit'))
                                        ->distinct()
                                        ->searchable()
                                        ->dehydrated()
                                        ->disabled(function ($get, $livewire) {
                                            $productId = $livewire->form->getRecord()?->id ?? null;
                                            $unitId    = $get('unit_id');

                                            if (! $productId || ! $unitId) {
                                                return false;
                                            }

                                            $isUsed =
                                                OrderDetails::where('product_id', $productId)->where('unit_id', $unitId)->exists() ||
                                                PurchaseInvoiceDetail::where('product_id', $productId)->where('unit_id', $unitId)->exists() ||
                                                InventoryTransaction::where('product_id', $productId)->where('unit_id', $unitId)->exists() ||
                                                StockIssueOrderDetail::where('product_id', $productId)->where('unit_id', $unitId)->exists();

                                            return $isUsed;
                                        })

                                        ->options(function () {
                                            return Unit::pluck('name', 'id');
                                        })->searchable()
                                        ->live()
                                        ->afterStateUpdated(function ($livewire, $set, $state, $get) {
                                            $packageSize   = $get('package_size') ?? 0;
                                            $productItems  = $get('../../productItems') ?? [];
                                            $totalNetPrice = collect($productItems)->sum('total_price_after_waste') ?? 0;
                                            $finalPrice    = $livewire->form->getRecord()->final_price ?? 0;
                                            if ($finalPrice == 0) {
                                                $finalPrice = $totalNetPrice;
                                            }
                                            $res = round($packageSize * $finalPrice, 8);
                                            $set('price', $res);
                                            $set('selling_price', $res);
                                        }),
                                    TextInput::make('package_size')
                                        ->numeric()->default(1)->required()
                                        ->minValue(0)
                                        ->rules(function (Get $get, callable $livewire) {
                                            return [
                                                function (string $attribute, $value, Closure $fail) use ($get, $livewire) {
                                                    $productId = $livewire->form->getRecord()?->id ?? null;
                                                    $unitId    = $get('unit_id');
                                                    $record    = $livewire->form->getRecord();

                                                    PRA::validatePackageSizeChange($productId, $unitId, $value, $fail, $record);
                                                },
                                            ];
                                        })
                                        ->live(onBlur: true)
                                        ->afterStateUpdated(function ($record, $livewire, $set, $state, $get) {
                                            $productItems  = $get('../../productItems') ?? [];
                                            $totalNetPrice = collect($productItems)->sum('total_price_after_waste') ?? 0;
                                            $finalPrice    = $livewire->form->getRecord()->final_price ?? 0;
                                            if ($finalPrice == 0) {
                                                $finalPrice = $totalNetPrice;
                                            }
                                            $res = round($state * $finalPrice, 8);
                                            $set('price', $res);
                                            $set('selling_price', $res);
                                        })
                                        ->extraInputAttributes(function (callable $get, $livewire, $record) {
                                            return PRA::isProductLocked($livewire->form->getRecord(), $record)
                                                ? ['readonly' => true]
                                                : [];
                                        })
                                        ->label(__('lang.package_size')),
                                    TextInput::make('price')
                                        ->numeric()
                                        ->default(function ($record, $livewire) {
                                            $finalPrice = $livewire->form->getRecord()->final_price ?? 0;
                                            return $finalPrice;
                                        })->minValue(0.0001)
                                        ->required()
                                        ->extraInputAttributes(function (callable $get, $livewire, $record) {
                                            return PRA::isProductLocked($livewire->form->getRecord(), $record)
                                                ? ['readonly' => true]
                                                : [];
                                        })
                                        ->label(__('lang.price')),
                                    TextInput::make('selling_price')
                                        ->numeric()
                                        ->minValue(1)
                                        ->label(__('lang.selling_price'))
                                        ->default(function ($record, $livewire) {
                                            $finalPrice = $livewire->form->getRecord()->final_price ?? 0;
                                            return $finalPrice;
                                        })
                                    // ->default(function ($record, $livewire) {
                                    //     return 0;
                                    //     // يمكن تعديل هذا الحساب حسب منطقك إن كان هناك ربط بالهامش أو غيره
                                    //     $finalPrice = $livewire->form->getRecord()->final_price ?? 0;
                                    //     return $finalPrice > 0 ? round($finalPrice * 1.2, 2) : null;
                                    // })
                                    ,

                                ])->orderColumn('order')
                                ->reorderable()

                            // ->disabled(function (callable $get, $livewire) {
                            //     return PRA::isProductLocked($livewire->form->getRecord());
                            // })

                        ]),

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
