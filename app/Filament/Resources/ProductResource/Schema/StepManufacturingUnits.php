<?php

namespace App\Filament\Resources\ProductResource\Schema;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Hidden;
use Filament\Schemas\Components\Wizard\Step;
use App\Filament\Resources\ProductResource\Support\ProductResourceActions as PRA;
use App\Models\Category;
use App\Models\InventoryTransaction;
use App\Models\OrderDetails;
use App\Models\PurchaseInvoiceDetail;
use App\Models\StockIssueOrderDetail;
use App\Models\Unit;
use App\Models\UnitPrice;
use App\Models\Product;
use Closure;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Repeater\TableColumn;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Utilities\Get;

class StepManufacturingUnits
{
    public static function repeater(): Repeater
    {
        return Repeater::make('units')->label(__('lang.final_price') ?? 'Final Price')
            ->visible(function (Get $get, $livewire): bool {
                $items = $get('productItems');
                if ($items !== null) {
                    return is_array($items) && count($items) > 0;
                }
                $record = method_exists($livewire, 'form') ? $livewire->form->getRecord() : null;
                if ($record && ($record->productItems()->exists() || $record->allUnitPrices()->exists())) {
                    return true;
                }
                return false;
            })
            ->columns(5)
            // ->hiddenOn(Pages\EditProduct::class)
            ->helperText(function (callable $get, $livewire, $record) {
                if (PRA::isProductLocked($livewire->form->getRecord(), $record)) {
                    return '⚠️ You cannot edit units because this product has related transactions.' . "\n" . 'However, you are allowed to add new units that will be used for manufacturing';
                }
                return null;
            })
            ->table([
                TableColumn::make(__('Unit'))->alignCenter()->width('16rem'),
                TableColumn::make('Price Before Waste')->alignCenter()->width('14rem'),
                TableColumn::make(__('Price'))->alignCenter()->width('12rem'),
                TableColumn::make(__('Selling'))->alignCenter()->width('14rem'),
                TableColumn::make(__('Weight'))->alignCenter()->width('12rem'),
            ])

            ->columnSpanFull()
            ->minItems(1)
            ->maxItems(1)
            ->defaultItems(1)
            ->addable(false)
            ->deletable(false)
            ->collapsible(false)
            ->relationship('allUnitPrices')
            ->mutateRelationshipDataBeforeFillUsing(function (array $data, $livewire): array {
                if (! empty($data['id'])) {
                    $unitPrice = UnitPrice::find($data['id']);
                    $data['price_before_waste'] = $unitPrice?->price_before_waste !== null ? number_format((float) $unitPrice->price_before_waste, 2, '.', '') : null;
                    $data['price'] = $unitPrice?->price !== null ? number_format((float) $unitPrice->price, 2, '.', '') : null;
                    $data['selling_price'] = $unitPrice?->selling_price !== null ? number_format((float) $unitPrice->selling_price, 2, '.', '') : null;
                } else {
                    $product = method_exists($livewire, 'getRecord') ? $livewire->getRecord() : null;
                    if (! $product && method_exists($livewire, 'form')) {
                        $product = $livewire->form->getRecord();
                    }
                    $data['price_before_waste'] = $product?->price_before_waste !== null ? number_format((float) $product->price_before_waste, 2, '.', '') : null;
                    $data['price'] = $product?->final_price !== null ? number_format((float) $product->final_price, 2, '.', '') : null;
                    $data['selling_price'] = $product?->final_price !== null ? number_format((float) $product->final_price, 2, '.', '') : null;
                }
                return $data;
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
                    ->rules(function (Get $get, callable $livewire) {
                        return [
                            function (string $attribute, $value, Closure $fail) use ($get) {
                                $units = (is_array($value) && !empty($value)) ? $value : ($get('units') ?? []);

                                // Validate units package size order
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
                                $packageSize   = $get('package_size') ?? 1;
                                $productItems  = $get('../../productItems') ?? $get('../productItems') ?? $get('productItems') ?? [];
                                $totalNetPrice = collect($productItems)->sum('total_price_after_waste') ?? 0;
                                $finalPrice    = $livewire->form->getRecord()?->final_price ?? 0;
                                if ($finalPrice == 0) {
                                    $finalPrice = $totalNetPrice;
                                }
                                $res = number_format((float) ($packageSize * $finalPrice), 2, '.', '');
                                $set('price', $res);
                                $set('selling_price', $res);

                                $totalBeforeWaste = PRA::calculateTotalBeforeWaste($productItems);
                                if ($totalBeforeWaste == 0) {
                                    $record = method_exists($livewire, 'form') ? $livewire->form->getRecord() : null;
                                    if ($record && method_exists($record, 'productItems')) {
                                        $totalBeforeWaste = (float) ($record->productItems()->sum('total_price') ?? 0);
                                    }
                                }
                                $set('price_before_waste', number_format((float) ($packageSize * $totalBeforeWaste), 2, '.', ''));
                            }),
                        Hidden::make('package_size')
                            ->default(1)
                            ->dehydrated(),
                        TextInput::make('price_before_waste')
                            ->prefix(settingWithDefault('currency_symbol', 'RM'))
                            ->numeric()
                            ->step('0.01')
                            ->label('Price Before Waste')
                            ->readOnly()
                            ->dehydrated(false)
                            ->formatStateUsing(function ($state, $record = null, $livewire = null) {
                                if ($state !== null && $state !== '') {
                                    return number_format((float) $state, 2, '.', '');
                                }

                                if ($record instanceof UnitPrice) {
                                    return number_format((float) ($record->price_before_waste ?? 0), 2, '.', '');
                                }

                                $product = method_exists($livewire, 'getRecord') ? $livewire->getRecord() : null;
                                if (! $product && $livewire && method_exists($livewire, 'form')) {
                                    $product = $livewire->form->getRecord();
                                }

                                return number_format((float) ($product?->price_before_waste ?? 0), 2, '.', '');
                            })
                            ->afterStateHydrated(function ($component, $state, $record = null, $livewire = null, ?Get $get = null) {
                                if ($state !== null && $state !== '') {
                                    $component->state(number_format((float) $state, 2, '.', ''));
                                    return;
                                }

                                if ($record instanceof UnitPrice) {
                                    $component->state(number_format((float) ($record->price_before_waste ?? 0), 2, '.', ''));
                                    return;
                                }

                                $product = method_exists($livewire, 'getRecord') ? $livewire->getRecord() : null;
                                if (! $product && $livewire && method_exists($livewire, 'form')) {
                                    $product = $livewire->form->getRecord();
                                }

                                if ($product && (float) $product->price_before_waste > 0) {
                                    $component->state(number_format((float) $product->price_before_waste, 2, '.', ''));
                                    return;
                                }

                                if ($get) {
                                    $productItems = $get('../../productItems') ?? $get('../productItems') ?? $get('productItems') ?? [];
                                    $component->state(number_format((float) PRA::calculateTotalBeforeWaste($productItems), 2, '.', ''));
                                }
                            })
                            ->default(function ($record = null, $livewire = null, ?Get $get = null) {
                                if ($record instanceof UnitPrice) {
                                    return number_format((float) ($record->price_before_waste ?? 0), 2, '.', '');
                                }

                                $product = method_exists($livewire, 'getRecord') ? $livewire->getRecord() : null;
                                if (! $product && $livewire && method_exists($livewire, 'form')) {
                                    $product = $livewire->form->getRecord();
                                }

                                if ($product && (float) $product->price_before_waste > 0) {
                                    return number_format((float) $product->price_before_waste, 2, '.', '');
                                }

                                if ($get) {
                                    $productItems = $get('../../productItems') ?? $get('../productItems') ?? $get('productItems') ?? [];
                                    return number_format((float) PRA::calculateTotalBeforeWaste($productItems), 2, '.', '');
                                }

                                return '0.00';
                            })
                            ->extraAttributes(['class' => 'bg-readonly-gray'])
                            ->extraInputAttributes(['class' => 'cursor-not-allowed']),
                        TextInput::make('price')
                            ->prefix(settingWithDefault('currency_symbol', 'RM'))
                            ->numeric()
                            ->step('0.01')
                            ->formatStateUsing(function ($state, $record = null, $livewire = null) {
                                if ($state !== null && $state !== '') {
                                    return number_format((float) $state, 2, '.', '');
                                }
                                $finalPrice = $livewire?->form?->getRecord()?->final_price ?? 0;
                                return number_format((float) $finalPrice, 2, '.', '');
                            })
                            ->afterStateHydrated(function ($component, $state, $record = null, $livewire = null) {
                                if ($state !== null && $state !== '') {
                                    $component->state(number_format((float) $state, 2, '.', ''));
                                    return;
                                }
                                $finalPrice = $livewire?->form?->getRecord()?->final_price ?? 0;
                                $component->state(number_format((float) $finalPrice, 2, '.', ''));
                            })
                            ->default(function ($record, $livewire) {
                                $finalPrice = $livewire?->form?->getRecord()?->final_price ?? 0;
                                return number_format((float) $finalPrice, 2, '.', '');
                            })->minValue(0.0001)
                            ->required()
                            ->readOnly()
                            ->dehydrateStateUsing(fn ($state) => filled($state) ? (float) $state : null)
                            ->extraAttributes(['class' => 'bg-readonly-gray'])
                            ->extraInputAttributes(['class' => 'cursor-not-allowed'])
                            ->label(__('lang.price')),
                        TextInput::make('selling_price')
                            ->prefix(settingWithDefault('currency_symbol', 'RM'))
                            ->numeric()
                            ->step('0.01')
                            ->minValue(0.01)
                            ->formatStateUsing(fn ($state) => filled($state) ? number_format((float) $state, 2, '.', '') : '0.00')
                            ->afterStateHydrated(function ($component, $state, $record = null, $livewire = null) {
                                if (filled($state)) {
                                    $component->state(number_format((float) $state, 2, '.', ''));
                                    return;
                                }
                                $finalPrice = $livewire?->form?->getRecord()?->final_price ?? 0;
                                $component->state(number_format((float) $finalPrice, 2, '.', ''));
                            })
                            ->live(onBlur: true)
                            ->afterStateUpdated(function (Set $set, $state) {
                                if (filled($state) && is_numeric($state)) {
                                    $set('selling_price', number_format((float) $state, 2, '.', ''));
                                }
                            })
                            ->label(__('lang.selling_price'))
                            ->default(function ($record, $livewire) {
                                $finalPrice = $livewire?->form?->getRecord()?->final_price ?? 0;
                                return number_format((float) $finalPrice, 2, '.', '');
                            })
                            ->dehydrateStateUsing(fn ($state) => filled($state) ? (float) $state : null),
                        TextInput::make('weight')
                            ->numeric()
                            ->nullable()
                            ->minValue(0)
                            ->label('Weight')
                            ->placeholder('Optional'),

                    ])->orderColumn('order')
                    ->reorderable();
    }

    public static function step(): Step
    {
        return Step::make('manafacturingProductunits')->label('Units')
            ->visible(fn($get): bool => ($get('category_id') !== null && Category::find($get('category_id'))->is_manafacturing))
            ->schema([
                static::repeater(),
            ]);
    }
}
