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
use Closure;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Repeater\TableColumn;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Utilities\Get;

class StepManufacturingUnits
{
    public static function step(): Step
    {
        return Step::make('manafacturingProductunits')->label('Units')
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
                        TableColumn::make(__('Weight'))->alignCenter()->width('10rem'),
                    ])

                    ->columnSpanFull()
                    ->minItems(1)
                    ->maxItems(1)
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
                        TextInput::make('weight')
                            ->numeric()
                            ->nullable()
                            ->minValue(0)
                            ->label('Weight')
                            ->placeholder('Optional'),

                    ])->orderColumn('order')
                    ->reorderable()

                // ->disabled(function (callable $get, $livewire) {
                //     return PRA::isProductLocked($livewire->form->getRecord());
                // })

            ]);
    }
}
