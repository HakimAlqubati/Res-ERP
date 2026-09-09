<?php

namespace App\Filament\Resources\ProductResource\Schema;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Wizard\Step;
use App\Filament\Resources\ProductResource\Support\ProductResourceActions as PRA;
use App\Models\Category;
use App\Models\InventoryTransaction;
use App\Models\OrderDetails;
use App\Models\PurchaseInvoiceDetail;
use App\Models\StockIssueOrderDetail;
use App\Models\Unit;
use App\Models\UnitPrice;
use Closure;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Repeater\TableColumn;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;

class StepUnmanufacturingUnits
{
    public static function step(): Step
    {
        return     Step::make('units')->label('Units')
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

                            ->numeric()->default(0)->required()->minValue(0.00000001)
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

            ])
        ;
    }
}
