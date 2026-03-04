<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\Product;
use App\Models\StockInventory;
use App\Models\StockInventoryDetail;
use App\Services\MultiProductsInventoryService;
use App\Models\User;
use App\Models\StockAdjustmentReason;
use App\Filament\Clusters\SupplierStoresReportsCluster\Resources\StockInventoryResource\RelationManagers\DetailsRelationManager;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Log;

use Spatie\Multitenancy\Jobs\TenantAware;

class GenerateUnauditedStocktakeJob implements ShouldQueue, TenantAware
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $startDate;
    public $endDate;
    public $hideZero;
    public $storeId;
    public $userId;

    public $timeout = 600; // allow for big calculations

    public function __construct($startDate, $endDate, $hideZero, $storeId, $userId)
    {
        $this->startDate = $startDate;
        $this->endDate = $endDate;
        $this->hideZero = $hideZero;
        $this->storeId = $storeId;
        $this->userId = $userId;
    }

    public function handle(): void
    {
        // Prevent timeout when running synchronously or on slow queues
        set_time_limit(0);

        Log::info('Generating unaudited stocktake for store: ' . $this->storeId);
        try {
            // 1. Fetch stock inventory IDs in the date range and store
            $inventoryIds = StockInventory::whereBetween('inventory_date', [$this->startDate, $this->endDate])
                ->where('store_id', $this->storeId)
                ->pluck('id');

            // 2. Fetch inventoried product IDs
            $inventoriedProductIds = StockInventoryDetail::whereIn('stock_inventory_id', $inventoryIds)
                ->pluck('product_id')
                ->unique();

            // 3. Build Query for Unaudited Products (active only)
            $query = Product::where('active', 1)
                ->whereNotIn('id', $inventoriedProductIds);

            if ($this->hideZero) {
                $bindings = [\App\Models\InventoryTransaction::MOVEMENT_IN, \App\Models\InventoryTransaction::MOVEMENT_OUT, $this->storeId];

                $query->whereRaw(
                    '(SELECT COALESCE(SUM(CASE WHEN movement_type = ? THEN IFNULL(base_quantity, quantity * package_size) ELSE 0 END), 0) - COALESCE(SUM(CASE WHEN movement_type = ? THEN IFNULL(base_quantity, quantity * package_size) ELSE 0 END), 0) FROM inventory_transactions WHERE product_id = products.id AND deleted_at IS NULL AND store_id = ?) > 0',
                    $bindings
                );
            }

            // Get products with their supply units eagerly loaded
            $products = $query->with('supplyOutUnitPrices.unit')->get();

            if ($products->isEmpty()) {
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

            Log::info('Number of products to process: ' . $products->count());
            // 5. Process EACH product, getting inventory quantities
            foreach ($products as $product) {
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
                    'is_adjustmented'    => 0,
                    'created_at'         => now(),
                    'updated_at'         => now(),
                ];
            }

            // 6. Bulk insert the details
            foreach (array_chunk($detailsBatch, 500) as $chunk) {
                StockInventoryDetail::insert($chunk);
            }

            // Fetch newly inserted details to run adjustment
            $insertedDetails = StockInventoryDetail::where('stock_inventory_id', $stockInventory->id)->get();

            if ($insertedDetails->isNotEmpty()) {
                // Call the existing logic to adjust stock down to 0 for these remaining quantities
                $adjustmentData = [
                    'store_id' => $this->storeId,
                    'reason_id' => StockAdjustmentReason::getFirstId(), // use default reason
                    'stock_adjustment_details' => $insertedDetails->map(function ($detail) use ($stockInventory) {
                        return [
                            'product_id' => $detail->product_id,
                            'unit_id' => $detail->unit_id,
                            'quantity' => $detail->difference, // this will be negative (e.g., -SystemQty)
                            'package_size' => $detail->package_size,
                            'notes' => "Auto-Adjustment for uninventoried product in stocktake #{$stockInventory->id}",
                        ];
                    })->toArray(),
                ];

                DetailsRelationManager::createStockAdjustment($adjustmentData, $insertedDetails);
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
