<?php

namespace App\Filament\Clusters\SupplierStoresReportsCluster\Resources\StockInventoryResource\RelationManagers;

use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Actions\BulkAction;
use Filament\Schemas\Components\Grid;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
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
use Filament\Forms;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DetailsRelationManager extends RelationManager
{
    protected static string $relationship = 'details';
    protected static ?string $title = '';

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
                TextColumn::make('difference')->alignCenter(true)->toggleable()->sortable(),
                IconColumn::make('is_adjustmented')->boolean()->alignCenter(true)->label(__('stock.is_adjustmented'))
                    ->toggleable()->sortable(),
                TextColumn::make('remaining_quantity')->label('Real Qty in Stock')
                    ->alignCenter(true)
                    ->getStateUsing(function ($record) {
                        $product = $record->product;
                        $storeId = defaultManufacturingStore($product)->id ?? null;
                        if (!$storeId) {
                            return 0;
                        }
                        $service = new  MultiProductsInventoryService(
                            null,
                            $record->product_id,
                            $record->unit_id,
                            $storeId
                        );
                        $remainingQty = $service->getInventoryForProduct($record->product_id)[0]['remaining_qty'] ?? 0;

                        return $remainingQty;
                    }),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                // Tables\Actions\CreateAction::make(),
            ])
            ->recordActions([
                // Tables\Actions\EditAction::make(),
                // Tables\Actions\DeleteAction::make(),
            ])

            ->toolbarActions([
                BulkAction::make('createStockAdjustment')
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
                                    ->label('Reason')->default(StockAdjustmentReason::getFirstId())
                                    ->options(StockAdjustmentReason::active()->pluck('name', 'id'))->searchable()
                                    ->required()
                                    ->live()
                                    ->afterStateUpdated(function ($state, callable $get, callable $set) {
                                        $details = $get('stock_adjustment_details') ?? [];
                                        $reason = is_numeric($state) ? StockAdjustmentReason::find((int) $state) : null;
                                        $reasonName = $reason?->name ?? '';
                                        // dd($details);
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
                                            ->getOptionLabelUsing(fn($value): ?string => Product::find($value)?->code . ' - ' . Product::find($value)?->name)
                                            ->columnSpan(2),
                                        Select::make('unit_id')
                                            ->label('Unit')
                                            ->required()
                                            ->options($records->pluck('unit.name', 'unit_id')->toArray()),
                                        TextInput::make('quantity')
                                            ->minValue(0)
                                            ->required(),
                                        TextInput::make('package_size')
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
                                    Log::alert('details', [$detail]);
                                    Log::alert('stockAdjustment', [$stockAdjustment]);
                                    Log::alert('allocations', [$allocations]);
                                    self::moveFromInventory($allocations, $stockAdjustment);
                                }

                                // if ($detail['quantity'] > 0) {
                                //     // Create a StockSupplyOrder
                                //     $order = StockSupplyOrder::create([
                                //         'order_date' => now(),
                                //         'store_id' => $data['store_id'],
                                //         'notes' => $stockAdjustment->notes,
                                //         'cancelled' => false,
                                //         'created_by' => $stockAdjustment->created_by,
                                //         'created_using_model_id' => $stockAdjustment->id,
                                //         'created_using_model_type' => StockAdjustmentDetail::class,
                                //     ]);


                                //     // Create StockSupplyOrderDetail for each detail
                                //     StockSupplyOrderDetail::create([
                                //         'stock_supply_order_id' => $order->id,
                                //         'product_id' => $detail['product_id'],
                                //         'unit_id' => $detail['unit_id'],
                                //         'quantity' => abs($detail['quantity']), // Convert to positive if negative
                                //         'package_size' => $detail['package_size'],
                                //     ]);
                                // } elseif ($detail['quantity'] < 0) {
                                //     // Create a StockIssueOrder
                                //     $order = StockIssueOrder::create([
                                //         'order_date' => now(),
                                //         'store_id' => $data['store_id'],
                                //         'notes' => $stockAdjustment->notes,
                                //         'cancelled' => false,
                                //         'created_by' => $stockAdjustment->created_by,
                                //         'created_using_model_id' => $stockAdjustment->id,
                                //         'created_using_model_type' => StockAdjustmentDetail::class,
                                //     ]);

                                //     // Create StockIssueOrderDetail for each detail
                                //     StockIssueOrderDetail::create([
                                //         'stock_issue_order_id' => $order->id,
                                //         'product_id' => $detail['product_id'],
                                //         'unit_id' => $detail['unit_id'],
                                //         'quantity' => abs($detail['quantity']), // Assuming quantity is used
                                //         'package_size' => $detail['package_size'],
                                //     ]);
                                // }
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
                    })
                    ->color('success')->icon('heroicon-o-plus')
                    ->deselectRecordsAfterCompletion(),
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
                'movement_date'        => $order->order_date ?? now(),
                'transaction_date'     => $order->order_date ?? now(),
                'store_id'             => $alloc['store_id'],
                'notes' => $alloc['notes'],

                'transactionable_id'   => $detail->id,
                'transactionable_type' => StockAdjustmentDetail::class,
                'source_transaction_id' => $alloc['transaction_id'],

            ]);
        }
        return;
    }
}
