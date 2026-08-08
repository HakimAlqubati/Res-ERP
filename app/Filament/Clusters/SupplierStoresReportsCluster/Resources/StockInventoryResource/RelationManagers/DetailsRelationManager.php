<?php

namespace App\Filament\Clusters\SupplierStoresReportsCluster\Resources\StockInventoryResource\RelationManagers;

use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Actions\BulkAction;
use Filament\Schemas\Components\Grid;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use App\Contracts\InventoryPriceResolver;
use App\Models\StockInventory;
use App\Models\InventoryTransaction;
use App\Services\FifoMethodService;
use Throwable;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use App\Models\Product;
use App\Models\StockAdjustment;
use App\Models\StockAdjustmentDetail;
use App\Models\StockAdjustmentReason;
use App\Models\StockIssueOrder;
use App\Models\StockIssueOrderDetail;
use App\Models\StockSupplyOrder;
use App\Models\StockSupplyOrderDetail;
use App\Models\Store;
use App\Services\MultiProductsInventoryService;
use Filament\Actions\Action;
use Filament\Forms;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Repeater\TableColumn;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use Filament\Tables;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class DetailsRelationManager extends RelationManager
{
    protected static string $relationship = 'details';
    protected static ?string $title = '';

    /**
     * Cached resolved prices — loaded once per request, no N+1.
     */
    protected ?Collection $resolvedPrices = null;

    protected function getResolvedPrices(): Collection
    {
        if ($this->resolvedPrices === null) {
            $resolver = app(InventoryPriceResolver::class);
            $this->resolvedPrices = $resolver->resolveForInventory($this->ownerRecord);
        }

        return $this->resolvedPrices;
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([]);
    }

    public function table(Table $table): Table
    {
        return $table->striped()
            ->columns([
                TextColumn::make('product.name')->searchable()->toggleable()
                    ->getStateUsing(function ($record) {
                        $product = $record->product;
                        return $product ? "{$product->code}-{$product->name}" : 'N/A';
                    }),
                TextColumn::make('unit.name')->searchable()->toggleable(),
                TextColumn::make('package_size')->alignCenter(true)->label(__('lang.package_size'))->toggleable(),
                TextColumn::make('system_quantity')->alignCenter(true)->toggleable()->sortable()
                    ->label('System Qty'),
                TextColumn::make('physical_quantity')
                    ->label('Physical Qty')
                    ->alignCenter(true)->toggleable()->sortable(),
                TextColumn::make('total_price')
                    ->label('Total Price')
                    ->alignCenter(true)->toggleable()
                    ->getStateUsing(function ($record) {
                        $key = $record->product_id . '_' . $record->unit_id;
                        $priceData = $this->getResolvedPrices()->get($key);
                        $unitPrice = $priceData ? (float) $priceData->unit_price : 0;
                        return (float)(($record->physical_quantity ?? 0) * $unitPrice);
                    })
                    ->formatStateUsing(fn($state) => formatMoneyWithCurrency($state))
                    ->summarize(
                        \Filament\Tables\Columns\Summarizers\Summarizer::make()
                            ->using(function (\Illuminate\Database\Query\Builder $query) {
                                $prices = $this->getResolvedPrices();
                                $records = $query->get();
                                $sum = 0;
                                foreach ($records as $record) {
                                    $key = $record->product_id . '_' . $record->unit_id;
                                    $priceData = $prices->get($key);
                                    $unitPrice = $priceData ? (float) $priceData->unit_price : 0;
                                    $sum += ($record->physical_quantity ?? 0) * $unitPrice;
                                }
                                return $sum;
                            })
                            ->formatStateUsing(fn($state) => formatMoneyWithCurrency($state))
                    ),
                TextColumn::make('difference')->alignCenter(true)->toggleable()->sortable(),
                IconColumn::make('is_adjustmented')->boolean()->alignCenter(true)->label(__('stock.is_adjustmented'))
                    ->toggleable()->sortable(),
              
            ])
            ->filters([
                //
            ])
            ->headerActions([
                // Tables\Actions\CreateAction::make(),
            ])
            ->recordActions([
                Action::make('edit_package_size')
                    ->label('Edit PKS')
                    ->icon('heroicon-o-pencil-square')
                    ->color('warning')
                    ->button()
                    ->schema([
                        TextInput::make('package_size')
                            ->label('Package Size')
                            ->numeric()
                            ->minValue(0.001)
                            ->required()
                            ->default(fn ($record) => $record->package_size),
                    ])
                    ->visible(fn()=> isHakimOrAdel())
                    ->hidden()
                    ->action(function ($record, array $data): void {
                        try {
                            $record->update(['package_size' => (float) $data['package_size']]);

                            Notification::make()
                                ->title('Package size updated')
                                ->body("Package size set to {$data['package_size']}.")
                                ->success()
                                ->send();
                        } catch (\Throwable $e) {
                            Notification::make()
                                ->title('Update failed')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),

                // Tables\Actions\DeleteAction::make(),
            ])

            ->toolbarActions([

                $this->bulkEditPhysicalQuantityAction(),
                $this->createStockAdjustmentAction(),
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->action(function (Collection $records, DeleteBulkAction $action) {
                            $nonAdjustmented = $records->filter(fn($record) => !$record->is_adjustmented);
                            $adjustmented = $records->filter(fn($record) => $record->is_adjustmented);

                            // Delete only non-adjusted records
                            $nonAdjustmented->each->delete();

                            // Show warning if some records were not deleted
                            if ($adjustmented->isNotEmpty()) {
                                showWarningNotifiMessage('Partial Deletion', 'Only non-adjusted records were deleted. Some records were skipped because they have already been adjusted.');
                            } else {
                                showSuccessNotifiMessage('Deleted', 'All selected records have been deleted successfully.');
                            }

                            // Optional: deselect records after action
                            $action->deselectRecordsAfterCompletion();
                        }),
                ]),
            ]);
    }

    public static function moveFromInventory($allocations, $detail)
    {
        foreach ($allocations as $alloc) {
            InventoryTransaction::create([
                'product_id'           => $detail->product_id,
                'movement_type'        => InventoryTransaction::MOVEMENT_OUT,
                'quantity'             => $alloc['deducted_qty'],
                'unit_id'              => $alloc['target_unit_id'],
                'package_size'         => $alloc['target_unit_package_size'],
                'price'                => $alloc['price_based_on_unit'],
                'movement_date'        =>  now(),
                'transaction_date'     =>  now(),
                'store_id'             => $alloc['store_id'],
                'notes' => $alloc['notes'],

                'transactionable_id'   => $detail->id,
                'transactionable_type' => StockAdjustmentDetail::class,
                'source_transaction_id' => $alloc['transaction_id'],

            ]);
        }
        return;
    }

    public static function createStockAdjustment($data, $records)
    {
        DB::beginTransaction();
        try {

            foreach ($data['stock_adjustment_details'] as $detail) {
                $defaultAdjustmentType = 0;
                if (isset($detail['quantity']) && is_numeric($detail['quantity'])) {
                    if ($detail['quantity'] <  0) {
                        $defaultAdjustmentType = StockAdjustment::ADJUSTMENT_TYPE_DECREASE;
                    } elseif ($detail['quantity'] > 0) {
                        $defaultAdjustmentType = StockAdjustment::ADJUSTMENT_TYPE_INCREASE;
                    } elseif ($detail['quantity'] == 0) {
                        $defaultAdjustmentType = StockAdjustment::ADJUSTMENT_TYPE_EQUAL;
                    }
                }

                $stockAdjustment = StockAdjustmentDetail::create([
                    'product_id' => $detail['product_id'],
                    'unit_id' => $detail['unit_id'],
                    'quantity' => abs($detail['quantity']),
                    'package_size' => $detail['package_size'],
                    'notes' => $detail['notes'],

                    'store_id' => $data['store_id'], // Adjust this based on your relationship
                    'reason_id' => $data['reason_id'], // You can set a reason if needed 
                    'adjustment_type' => $defaultAdjustmentType,
                    'created_by' => auth()->id(),
                    'adjustment_date' => now(),
                    'source_id' => $records->first()->stock_inventory_id ?? null,
                    'source_type' => StockInventory::class,
                ]);
                $notes = "Stock adjustment for product ({$stockAdjustment->product->name}) "
                    . "in unit '{$stockAdjustment->unit->name}' at store '{$stockAdjustment->store->name}', "
                    . "adjusted by " . auth()->user()?->name . " on " . now()->format('Y-m-d H:i');

                $type = $detail['quantity'] > 0
                    ? InventoryTransaction::MOVEMENT_IN
                    : InventoryTransaction::MOVEMENT_OUT;

                if ($type == 'in') {

                    InventoryTransaction::create([
                        'product_id' => $detail['product_id'],
                        'movement_type' => InventoryTransaction::MOVEMENT_IN,
                        'quantity' => abs((float) $detail['quantity']),
                        'unit_id' => $detail['unit_id'],
                        'movement_date' => now(),
                        'transaction_date' => now(),
                        'package_size' => $detail['package_size'],
                        'store_id' => $data['store_id'],
                        'price' => getUnitPrice($detail['product_id'], $detail['unit_id']), // إن أحببت
                        'notes' => $notes,
                        'transactionable_id' => $stockAdjustment->id,
                        'transactionable_type' => StockAdjustmentDetail::class,
                    ]);
                } else {
                    $fifoService = new FifoMethodService($stockAdjustment);
                    $allocations = $fifoService->getAllocateFifo(
                        $detail['product_id'],
                        $detail['unit_id'],
                        abs($detail['quantity']),
                        $data['store_id']
                    );

                    self::moveFromInventory($allocations, $stockAdjustment);
                }
            }
            // Update is_adjustmented field for selected records
            $records->each(function ($record) {
                $record->update(['is_adjustmented' => true]);
            });

            // Finalize the inventory if all details adjusted
            $inventory = $records->first()->inventory;

            $allAdjusted = $inventory->details()->where('is_adjustmented', false)->count() === 0;

            if ($allAdjusted) {
                $inventory->finalized = true;
                $inventory->save();
            }
            showSuccessNotifiMessage('done', 'Stock adjustment created successfully.');
            DB::commit();
        } catch (Throwable $th) {
            //throw $th;
            DB::rollBack();
            showWarningNotifiMessage('Faild', $th->getMessage());
        }
    }

    public function bulkEditPhysicalQuantityAction(): BulkAction{
        return BulkAction::make('editPhysicalQuantity')
                    ->label(__('Edit Physical Quantity'))
                    ->icon('heroicon-o-pencil-square')
                    ->slideOver(true)
                    ->color('info')
                    ->closeModalByClickingAway(false)
                    ->closeModalByEscaping(false)
                    ->modalIcon(Heroicon::ChartBarSquare)
                    ->modalWidth(Width::SevenExtraLarge)
                    ->modalDescription('to close this modal, click X top-right or Cancel button bottom-left')
                    ->schema(function (Collection $records) {
                        $storeId = $this->ownerRecord->store_id ?? null;
                        $defaultValues = $records->map(fn($record) => [
                            'id' => $record->id,
                            'product_id' => $record->product_id,
                            'store_id' => $storeId,
                            'product_name' => $record->product ? "{$record->product->code}-{$record->product->name}" : 'N/A',
                            'unit_id' => $record->unit_id,
                            'system_quantity' => $record->system_quantity,
                            'physical_quantity' => $record->physical_quantity,
                            'package_size' => $record->package_size,
                            'is_adjustmented' => $record->is_adjustmented,
                        ])->toArray();

                        return [
                            Repeater::make('items')
                                ->label(__('Products'))
                                ->table([
                                    TableColumn::make(__('lang.product'))->width('30%'),
                                    TableColumn::make(__('lang.unit'))->width('10%'),
                                    TableColumn::make(__('lang.package_size'))->width('10%'),
                                    TableColumn::make(__('lang.system_quantity'))->width('13%'),
                                    TableColumn::make(__('lang.physical_quantity'))
                                        ->alignCenter(true)
                                        ->width('15%'),
                                    TableColumn::make(__('lang.difference'))->width('12%'),
                                ])->columnSpanFull()
                                ->schema([
                                    Hidden::make('id'),
                                    Hidden::make('is_adjustmented'),
                                    Hidden::make('product_id'),
                                    Hidden::make('store_id'),
                                    TextInput::make('product_name')
                                        ->label(__('Product'))
                                        ->disabled()
                                        ->columnSpan(2),
                                    Select::make('unit_id')
                                        ->label(__('Unit'))
                                        ->options(function (callable $get) {
                                            $productId = $get('product_id');
                                            if (!$productId) {
                                                return [];
                                            }
                                            $product = Product::find($productId);
                                            if (!$product) {
                                                return [];
                                            }
                                            return $product->units()->pluck('units.name', 'units.id')->toArray();
                                        })
                                        ->disabled(fn($get) => $get('is_adjustmented'))
                                        ->live()
                                        ->afterStateUpdated(function ($state, callable $get, callable $set) {
                                            $productId = $get('product_id');
                                            $storeId = $get('store_id');

                                            if (!$productId || !$storeId || !$state) {
                                                return;
                                            }

                                            // 1. Fetch new package_size for selected unit
                                            $newPackageSize = \App\Models\UnitPrice::getPackageSize((int) $productId, (int) $state);

                                            if ($newPackageSize === null) {
                                                Notification::make()
                                                    ->title(__('Package Size Missing'))
                                                    ->body(__('No package size defined for the selected unit. Please configure it in the product settings.'))
                                                    ->danger()
                                                    ->send();
                                                $set('unit_id', null);
                                                return;
                                            }

                                            // 2. Convert physical_quantity based on package_size ratio
                                            $oldPackageSize = $get('package_size');
                                            $physicalQty = $get('physical_quantity') ?? 0;

                                            if ($oldPackageSize && $oldPackageSize > 0 && $newPackageSize > 0) {
                                                // $physicalQty = round($physicalQty * ($oldPackageSize / $newPackageSize), 4);
                                                // $set('physical_quantity', $physicalQty);
                                            }

                                            // 3. Update package_size to new value
                                            $set('package_size', $newPackageSize);

                                            // 4. Get system_quantity for new unit
                                            $report = MultiProductsInventoryService::quickReport(
                                                (int) $storeId,
                                                (int) $productId,
                                                (int) $state
                                            );

                                            $systemQty = $report[0][0]['remaining_qty'] ?? 0;
                                            $set('system_quantity', $systemQty);

                                            // 5. Recalculate difference with converted physical_quantity
                                            $diff = round($physicalQty - $systemQty, 4);
                                            $set('difference', $diff);
                                        })
                                        ->required(),
                                    TextInput::make('package_size')
                                        ->label(__('lang.package_size'))
                                        ->extraInputAttributes(['class' => 'text-center'])
                                        ->disabled()
                                        ->dehydrated(true),
                                    TextInput::make('system_quantity')
                                        ->label(__('System Qty'))
                                        ->dehydrated(true)
                                        ->extraInputAttributes(['class' => 'text-center'])

                                        ->disabled(),
                                    TextInput::make('physical_quantity')
                                        ->label(__('Physical Qty'))
                                        ->extraInputAttributes(['class' => 'text-center'])
                                        ->numeric()
                                        ->required()
                                        ->minValue(0)
                                        ->live(onBlur: true)
                                        ->disabled(fn($get) => $get('is_adjustmented'))
                                        ->afterStateUpdated(function ($state, callable $get, callable $set) {
                                            $systemQty = $get('system_quantity') ?? 0;
                                            $diff = $state - $systemQty;
                                            $diff = round($diff, 4);
                                            $set('difference', $diff);
                                        }),
                                    TextInput::make('difference')
                                        ->extraInputAttributes(['class' => 'text-center'])

                                        ->label(__('Difference'))
                                        ->disabled()
                                        ->dehydrated(false)
                                        ->default(fn($get) => ($get('physical_quantity') ?? 0) - ($get('system_quantity') ?? 0)),


                                ])
                                ->default($defaultValues)
                                ->addable(false)
                                ->deletable(false)
                                ->reorderable(false)
                                ->columnSpanFull(),
                        ];
                    })
                    ->action(function (Collection $records, array $data) {
                        DB::beginTransaction();
                        try {
                            foreach ($data['items'] as $item) {
                                if ($item['is_adjustmented']) {
                                    continue;
                                }
                                $record = $records->firstWhere('id', $item['id']);
                                if ($record) {
                                    if (empty($item['package_size']) && $item['package_size'] !== 0) {
                                        throw new \Exception("Package size is missing for product: {$record->product?->name}. Cannot save without a valid package size.");
                                    }
                                    $record->update([
                                        'physical_quantity' => $item['physical_quantity'],
                                        'unit_id'           => $item['unit_id'],
                                        'system_quantity'   => $item['system_quantity'],
                                        'package_size'      => $item['package_size'],
                                        'difference'        => $item['physical_quantity'] - $item['system_quantity'],
                                    ]);
                                }
                            }

                            DB::commit();
                            Notification::make()
                                ->title(__('Updated Successfully'))
                                ->body(__('Physical quantities have been updated'))
                                ->success()
                                ->send();
                        } catch (\Throwable $th) {
                            DB::rollBack();
                            Notification::make()
                                ->title(__('Error'))
                                ->body($th->getMessage())
                                ->danger()
                                ->send();
                        }
                    })
                    ->deselectRecordsAfterCompletion();
    }   

    public   function createStockAdjustmentAction(): BulkAction 
    {
        return BulkAction::make('createStockAdjustment')
                    ->closeModalByClickingAway(false)
                    ->label('Create Stock Adjustment')
                    ->closeModalByEscaping(false)
                    ->stickyModalHeader(true)
                    ->slideOver(true) 
                    ->modalCloseButton(false)
                    ->modalIcon(Heroicon::ChartBarSquare)
                    ->modalWidth(Width::SevenExtraLarge)
                    ->modalDescription('to close this modal, click X top-right or Cancel button bottom-left')
                    ->schema(function (Collection $records) {

                        $defaultValues = $records
                            ->filter(fn($record) => !$record->is_adjustmented)
                            ->map(fn($record) => [
                                'product_id' => $record->product_id,
                                'unit_id' => $record->unit_id,
                                'quantity' => $record->difference,
                                'package_size' => $record->package_size
                            ])
                            ->toArray();

                        return [
                            Grid::make()->columns(2)->columnSpanFull()->schema([
                                Select::make('reason_id')
                                    ->label('Reason')
                                    ->default(StockAdjustmentReason::getFirstId())
                                    ->options(StockAdjustmentReason::active()->pluck('name', 'id'))->searchable()
                                    ->required()
                                    ->live()
                                    ->afterStateUpdated(function ($state, callable $get, callable $set) {
                                        $details = $get('stock_adjustment_details') ?? [];
                                        $reason = is_numeric($state) ? StockAdjustmentReason::find((int) $state) : null;
                                        $reasonName = $reason?->name ?? '';
                                        foreach ($details as $index => $item) {
                                            $productId = $item['product_id'] ?? null;
                                            $product = is_numeric($productId) ? Product::find((int) $productId) : null;
                                            $productName = $product?->name ?? '';

                                            $note = trim("{$reasonName} on product ({$productName})") . ' in stocktake #' . $this->ownerRecord->id;

                                            $set("stock_adjustment_details.{$index}.notes", $note);
                                        }
                                    }),
                                Select::make('store_id')
                                    ->label(__('lang.store'))

                                    ->default(function () {
                                        return $this->ownerRecord->store_id ?? null;
                                    })
                                    ->disabled()->dehydrated()
                                    ->options(
                                        Store::active()
                                            ->withManagedStores()
                                            ->get(['name', 'id'])->pluck('name', 'id')
                                    )->required(),

                            ]),
                            Repeater::make('stock_adjustment_details')->columnSpanFull()
                                // ->relationship('details')
                                ->schema([
                                    Grid::make()->columns(5)->columnSpanFull()->schema([
                                        Select::make('product_id')
                                            ->label('Product')
                                            ->required()->searchable()
                                            ->options(function () {
                                                return Product::where('active', 1)
                                                    ->get(['name', 'id', 'code'])
                                                    ->mapWithKeys(fn($product) => [
                                                        $product->id => "{$product->code} - {$product->name}"
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
                                                        $product->id => "{$product->code} - {$product->name}"
                                                    ])
                                                    ->toArray();
                                            })
                                            ->disabled()
                                            ->dehydrated()
                                            ->getOptionLabelUsing(fn($value): ?string => Product::find($value)?->code . ' - ' . Product::find($value)?->name)
                                            ->columnSpan(2),
                                        Select::make('unit_id')
                                            ->label('Unit')
                                            ->required()
                                              ->disabled()
                                            ->dehydrated()
                                            ->options($records->pluck('unit.name', 'unit_id')->toArray()),
                                        TextInput::make('quantity')
                                            ->numeric()
                                            // ->minValue(0)
                                            // ->maxValue(99999)
                                              ->disabled()
                                            ->dehydrated()
                                            ->rules([
                                                'numeric',
                                                // 'min:0',
                                                'max:99999',
                                            ])
                                            ->required(),
                                        TextInput::make('package_size')
                                            ->readOnly()
                                            ->label('Qty per Pack')
                                            ->disabled()
                                            ->dehydrated()
                                            ->required(),

                                    ]),
                                    Textarea::make('notes')->columnSpanFull()->helperText('Type Reason ...')
                                        ->default(function ($get) {
                                            $reason = optional(StockAdjustmentReason::find($get('../../reason_id')))->name;
                                            $product = optional(Product::find($get('product_id')))->name;
                                            return trim("{$reason} on product ({$product}") .  ') in stocktake #' . $this->ownerRecord->id;
                                        })
                                        ->required(),
                                ])->addable(false)->minItems(1)
                                ->defaultItems(1)->addActionLabel('Add Item')
                                ->default($defaultValues)
                                ->columns(4)
                        ];
                    })
                    ->action(function (Collection $records, $data) {
                        static::createStockAdjustment($data, $records);
                    })
                    ->color('success')->icon('heroicon-o-plus')
                    ->deselectRecordsAfterCompletion();
    }
}
