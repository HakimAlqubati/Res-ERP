<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Bus\Queueable;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\Product;
use App\Models\StockInventory;
use App\Models\StockInventoryDetail;
use App\Services\MultiProductsInventoryService;
use App\Models\User;
use App\Models\StockAdjustment;
use App\Models\StockAdjustmentDetail;
use App\Services\FifoMethodService;
use App\Models\StockAdjustmentReason;
use App\Models\AppLog;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class GenerateUnauditedStocktakeJob implements ShouldQueue
{
    use Dispatchable,
        InteractsWithQueue,
        Queueable,
        SerializesModels;

    public $startDate;
    public $endDate;
    public $hideZero;
    public $storeId;
    public $userId;
    // public $tenantId;

    // public $timeout = 600; // allow for big calculations

    public function __construct($startDate, $endDate, $hideZero, $storeId, $userId)
    {
        $this->startDate = $startDate;
        $this->endDate = $endDate;
        $this->hideZero = $hideZero;
        $this->storeId = $storeId;
        $this->userId = $userId;

        // Force connection to landlord database queue
        $this->onConnection('database');
    }

    public function handle(): void
    {
        Log::info('Generating unaudited stocktake for store: ' . $this->storeId);
        try {
            // 1. Fetch stock inventory using unified service method
            $products = \App\Services\StockInventoryReportService::getProductsNotInventoriedBetween(
                $this->startDate,
                $this->endDate,
                1000,
                $this->storeId,
                true
            );


            Log::info('startDate ', [$this->startDate]);
            Log::info('endDate ', [$this->endDate]);
            Log::info('storeId ', [$this->storeId]);
           
            if (count($products->items()) == 0) {
                Log::info('No unaudited products found based on your parameters.');
                $this->notifyUser('Stocktake Generation Failed', 'No unaudited products found based on your parameters.', 'warning');
                return;
            }

            // 4. Create the Stock Inventory Header
            $stockInventory = StockInventory::create([
                'inventory_date'      => now(),
                'store_id'            => $this->storeId,
                'responsible_user_id' => $this->userId,
                'created_by'          => $this->userId,
                'finalized'           => 0,
            ]);

            $detailsBatch = [];

            Log::info('Number of products to process: ' . $products->total());
            // 5. Process EACH product, getting inventory quantities
            foreach ($products->items() as $product) {
                $unitPrices = $product->supplyOutUnitPrices ?? collect();
                // We use the smallest unit by default, similar to the UI logic
                $selectedUnit = $unitPrices->sortBy('package_size')->first();
                $firstUnitId = $selectedUnit?->unit_id;
                $packageSize = (float)($selectedUnit?->package_size ?? 0);

                if (!$firstUnitId) { // Fallback if no specific supply out unit is set
                    continue;
                }

                $service = new MultiProductsInventoryService(null, $product->id, $firstUnitId, $this->storeId);
                $inventoryReport = $service->getInventoryForProduct($product->id);
                $remainingQty = (float) ($inventoryReport[0]['remaining_qty'] ?? 0);

                $detailsBatch[] = [
                    'stock_inventory_id' => $stockInventory->id,
                    'product_id'         => $product->id,
                    'unit_id'            => $firstUnitId,
                    'package_size'       => $packageSize,
                    'system_quantity'    => $remainingQty,
                    'physical_quantity'  => 0, // By default, physical = 0, user will edit
                    'difference'         => 0 - ($remainingQty), // difference = physical - system
                    'is_adjustmented'    => 1, // Marked as 1 because we explicitly adjust them below
                    'created_at'         => now(),
                    'updated_at'         => now(),
                ];
            }

            // 6. Bulk insert the details
            foreach (array_chunk($detailsBatch, 500) as $chunk) {
                StockInventoryDetail::insert($chunk);
            }

            // Fetch newly inserted details to run adjustment
            $insertedDetails = StockInventoryDetail::where('stock_inventory_id', $stockInventory->id)
                ->where('difference', '<', 0) // Only process items that need negative adjustment
                ->get();

            if ($insertedDetails->isNotEmpty()) {
                DB::beginTransaction();
                try {
                    $reasonId = StockAdjustmentReason::getFirstId();

                    foreach ($insertedDetails as $detail) {
                        $qtyToDeduct = abs($detail->difference);

                        // 1. Create the adjustment detail log
                        $stockAdjustment = StockAdjustmentDetail::create([
                            'product_id' => $detail->product_id,
                            'unit_id' => $detail->unit_id,
                            'quantity' => $qtyToDeduct,
                            'package_size' => $detail->package_size,
                            'notes' => "Auto-Adjustment for uninventoried product in stocktake #{$stockInventory->id}",

                            'store_id' => $this->storeId,
                            'reason_id' => $reasonId,
                            'adjustment_type' => StockAdjustment::ADJUSTMENT_TYPE_DECREASE,
                            'created_by' => $this->userId,
                            'adjustment_date' => now(),
                            'source_id' => $stockInventory->id,
                            'source_type' => StockInventory::class,
                        ]);

                        // 2. Perform Fifo Deduction
                        $fifoService = new FifoMethodService($stockAdjustment);
                        $allocations = $fifoService->getAllocateFifo(
                            $detail->product_id,
                            $detail->unit_id,
                            $qtyToDeduct,
                            $this->storeId
                        );

                        // 3. Move from inventory loop
                        foreach ($allocations as $alloc) {
                            \App\Models\InventoryTransaction::create([
                                'product_id'           => $detail->product_id,
                                'movement_type'        => \App\Models\InventoryTransaction::MOVEMENT_OUT,
                                'quantity'             => $alloc['deducted_qty'],
                                'base_quantity'        => $alloc['deducted_qty'] * ($alloc['target_unit_package_size'] ?? 1),
                                'unit_id'              => $alloc['target_unit_id'],
                                'price'                => $alloc['price_based_on_unit'],
                                'package_size'         => $alloc['target_unit_package_size'],
                                'movement_date'        => now(),
                                'transaction_date'     => now(),
                                'store_id'             => $alloc['store_id'],
                                'notes'                => $alloc['notes'],

                                'transactionable_id'   => $stockAdjustment->id,
                                'transactionable_type' => StockAdjustmentDetail::class,
                                'source_transaction_id' => $alloc['transaction_id'],
                            ]);
                        }
                    }

                    DB::commit();

                    $stockInventory->finalized = true;
                    $stockInventory->save();
                } catch (\Throwable $th) {
                    DB::rollBack();
                    Log::error('Background adjustment failed', ['error' => $th->getMessage()]);
                    throw $th; // re-throw to be caught by the outer catch block
                }
            }

            // 7. Notify Success
            $this->notifyUser(
                'Stocktake Generation Successful',
                'Unaudited stocktake was generated successfully with ' . count($detailsBatch) . ' items.',
                'success',
                $stockInventory->id
            );
        } catch (\Throwable $e) {
            Log::error('Job GenerateUnauditedStocktakeJob Failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            $this->notifyUser('Stocktake Generation Failed', 'An error occurred during background generation. Check logs for details.', 'danger');
        }
    }

    private function notifyUser($title, $body, $color, $recordId = null)
    {
        $user = User::find($this->userId);
        if (!$user) return;

        $notification = Notification::make()
            ->title($title)
            ->body($body);

        if ($color === 'success') {
            $notification->success();
        } elseif ($color === 'danger') {
            $notification->danger();
        } else {
            $notification->warning();
        }

        if ($recordId) {
            $notification->actions([
                Action::make('view_stocktake')
                    ->label('View Stocktake')
                    ->url('/admin/supplier-stores-reports-cluster/stock-inventories/' . $recordId)
                    ->button()
            ]);
        }

        $notification->sendToDatabase($user);
    }
}
