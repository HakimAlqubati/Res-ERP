<?php

namespace App\Services\Inventory\StockAdjustment;

use App\Models\InventoryTransaction;
use App\Models\StockAdjustment;
use App\Models\StockAdjustmentDetail;
use App\Models\StockInventory;
use App\Repositories\Inventory\StockAdjustment\Contracts\StockAdjustmentRepositoryInterface;
use App\Services\FifoMethodService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Jobs\ZeroStoreStockJob;
use Exception;

class StockAdjustmentService
{
    /**
     * @var StockAdjustmentRepositoryInterface
     */
    protected StockAdjustmentRepositoryInterface $repository;

    /**
     * StockAdjustmentService constructor.
     *
     * @param StockAdjustmentRepositoryInterface $repository
     */
    public function __construct(StockAdjustmentRepositoryInterface $repository)
    {
        $this->repository = $repository;
    }

    /**
     * Get paginated stock adjustments
     *
     * @param array $filters
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getPaginated(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return $this->repository->getPaginated($filters, $perPage);
    }

    /**
     * Create adjustment from a single StockInventoryDetail
     * Logic matches Filament DetailsRelationManager exactly
     *
     * @param int $detailId
     * @param array $data Must contain: quantity, product_id, unit_id, package_size, store_id, reason_id, notes (optional)
     * @return StockAdjustmentDetail
     */
    public function createFromInventoryDetail(int $detailId, array $data = []): StockAdjustmentDetail
    {
        return DB::transaction(function () use ($detailId, $data) {
            $detail = \App\Models\StockInventoryDetail::with(['inventory'])->findOrFail($detailId);

            // Get quantity from data (like Filament - already calculated difference)
            $quantity = (float) ($data['quantity'] ?? 0);

            // Determine adjustment type based on quantity sign (like Filament)
            $defaultAdjustmentType = StockAdjustmentDetail::ADJUSTMENT_TYPE_EQUAL;
            if ($quantity < 0) {
                $defaultAdjustmentType = StockAdjustmentDetail::ADJUSTMENT_TYPE_DECREASE;
            } elseif ($quantity > 0) {
                $defaultAdjustmentType = StockAdjustmentDetail::ADJUSTMENT_TYPE_INCREASE;
            }

            // Create StockAdjustmentDetail (like Filament)
            $stockAdjustment = StockAdjustmentDetail::create([
                'product_id' => $data['product_id'] ?? $detail->product_id,
                'unit_id' => $data['unit_id'] ?? $detail->unit_id,
                'quantity' => abs($quantity),
                'package_size' => $data['package_size'] ?? $detail->package_size,
                'notes' => $data['notes'] ?? null,
                'store_id' => $data['store_id'] ?? $detail->inventory->store_id,
                'reason_id' => $data['reason_id'] ?? null,
                'adjustment_type' => $defaultAdjustmentType,
                'created_by' => auth()->id(),
                'adjustment_date' => now(),
                'source_id' => $detail->stock_inventory_id,
                'source_type' => StockInventory::class,
            ]);

            // Create Inventory Transaction (like Filament)
            if ($quantity != 0) {
                // Load relationships for notes
                $stockAdjustment->loadMissing(['product', 'unit', 'store']);

                $notes = "Stock adjustment for product ({$stockAdjustment->product->name}) "
                    . "in unit '{$stockAdjustment->unit->name}' at store '{$stockAdjustment->store->name}', "
                    . "adjusted by " . auth()->user()?->name . " on " . now()->format('Y-m-d H:i');

                $type = $quantity > 0
                    ? InventoryTransaction::MOVEMENT_IN
                    : InventoryTransaction::MOVEMENT_OUT;

                if ($type == InventoryTransaction::MOVEMENT_IN) {
                    InventoryTransaction::create([
                        'product_id' => $stockAdjustment->product_id,
                        'movement_type' => InventoryTransaction::MOVEMENT_IN,
                        'quantity' => abs($quantity),
                        'unit_id' => $stockAdjustment->unit_id,
                        'movement_date' => now(),
                        'transaction_date' => now(),
                        'package_size' => $stockAdjustment->package_size,
                        'store_id' => $stockAdjustment->store_id,
                        'price' => getUnitPrice($stockAdjustment->product_id, $stockAdjustment->unit_id),
                        'notes' => $notes,
                        'transactionable_id' => $stockAdjustment->id,
                        'transactionable_type' => StockAdjustmentDetail::class,
                    ]);
                } else {
                    // Use FIFO for decrease (like Filament)
                    $fifoService = new FifoMethodService($stockAdjustment);
                    $allocations = $fifoService->getAllocateFifo(
                        $stockAdjustment->product_id,
                        $stockAdjustment->unit_id,
                        abs($quantity),
                        $stockAdjustment->store_id
                    );

                    foreach ($allocations as $alloc) {
                        InventoryTransaction::create([
                            'product_id' => $stockAdjustment->product_id,
                            'movement_type' => InventoryTransaction::MOVEMENT_OUT,
                            'quantity' => $alloc['deducted_qty'],
                            'unit_id' => $alloc['target_unit_id'],
                            'package_size' => $alloc['target_unit_package_size'],
                            'price' => $alloc['price_based_on_unit'],
                            'movement_date' => now(),
                            'transaction_date' => now(),
                            'store_id' => $stockAdjustment->store_id,
                            'notes' => $alloc['notes'] ?? $notes,
                            'transactionable_id' => $stockAdjustment->id,
                            'transactionable_type' => StockAdjustmentDetail::class,
                            'source_transaction_id' => $alloc['transaction_id'],
                        ]);
                    }
                }
            }

            // Mark detail as adjusted (like Filament - only is_adjustmented)
            $detail->update(['is_adjustmented' => true]);

            // Finalize the inventory if all details adjusted (like Filament)
            $inventory = $detail->inventory;
            if ($inventory) {
                $allAdjusted = $inventory->details()->where('is_adjustmented', false)->count() === 0;
                if ($allAdjusted) {
                    $inventory->finalized = true;
                    $inventory->save();
                }
            }

            return $stockAdjustment;
        });
    }

    /**
     * Create adjustment from StockInventory differences
     *
     * @param StockInventory $inventory
     * @return void
     * @throws Exception
     */
    public function createFromInventory(StockInventory $inventory): void
    {
        DB::transaction(function () use ($inventory) {
            $inventory->loadMissing('details.product', 'details.unit');

            foreach ($inventory->details as $detail) {
                if ($detail->is_adjustmented) continue;

                $this->createFromInventoryDetail($detail->id);
            }
        });
    }

    /**
     * Create a manual stock adjustment
     *
     * @param array $data
     * @return StockAdjustmentDetail
     * @throws Exception
     */
    public function createManualAdjustment(array $data): StockAdjustmentDetail
    {
        return DB::transaction(function () use ($data) {
            $data['created_by'] = auth()->id();
            $adjustmentRecord = $this->repository->create($data);

            $this->createInventoryTransaction($adjustmentRecord);

            return $adjustmentRecord;
        });
    }

    /**
     * Create Inventory Transaction for an adjustment
     *
     * @param StockAdjustmentDetail $adjustment
     * @return void
     * @throws Exception
     */
    protected function createInventoryTransaction(StockAdjustmentDetail $adjustment): void
    {
        // Load relationships if not loaded
        $adjustment->loadMissing(['product', 'unit', 'store']);

        // Build notes like Filament format
        $notes = $adjustment->notes ?? ("Stock adjustment for product ({$adjustment->product->name}) "
            . "in unit '{$adjustment->unit->name}' at store '{$adjustment->store->name}', "
            . "adjusted by " . auth()->user()?->name . " on " . now()->format('Y-m-d H:i'));

        $movementType = $adjustment->adjustment_type === 'increase'
            ? InventoryTransaction::MOVEMENT_IN
            : InventoryTransaction::MOVEMENT_OUT;

        if ($movementType === InventoryTransaction::MOVEMENT_IN) {
            InventoryTransaction::create([
                'product_id' => $adjustment->product_id,
                'movement_type' => InventoryTransaction::MOVEMENT_IN,
                'quantity' => $adjustment->quantity,
                'unit_id' => $adjustment->unit_id,
                'movement_date' => $adjustment->adjustment_date,
                'transaction_date' => now(),
                'package_size' => $adjustment->package_size,
                'store_id' => $adjustment->store_id,
                'price' => getUnitPrice($adjustment->product_id, $adjustment->unit_id),
                'notes' => $notes,
                'transactionable_id' => $adjustment->id,
                'transactionable_type' => StockAdjustmentDetail::class,
            ]);
        } else {
            // Use FIFO for decrease
            $fifoService = new FifoMethodService($adjustment);

            $allocations = $fifoService->getAllocateFifo(
                $adjustment->product_id,
                $adjustment->unit_id,
                $adjustment->quantity,
                $adjustment->store_id
            );

            foreach ($allocations as $alloc) {
                InventoryTransaction::create([
                    'product_id' => $adjustment->product_id,
                    'movement_type' => InventoryTransaction::MOVEMENT_OUT,
                    'quantity' => $alloc['deducted_qty'],
                    'unit_id' => $alloc['target_unit_id'],
                    'package_size' => $alloc['target_unit_package_size'],
                    'price' => $alloc['price_based_on_unit'],
                    'movement_date' => $adjustment->adjustment_date,
                    'transaction_date' => now(),
                    'store_id' => $adjustment->store_id,
                    'notes' => $alloc['notes'] ?? $notes,
                    'transactionable_id' => $adjustment->id,
                    'transactionable_type' => StockAdjustmentDetail::class,
                    'source_transaction_id' => $alloc['transaction_id'],
                ]);
            }
        }
    }

    /**
     * Delete an adjustment and its transactions
     *
     * @param int $id
     * @return bool
     * @throws Exception
     */
    public function deleteAdjustment(int $id): bool
    {
        return DB::transaction(function () use ($id) {
            $adjustment = $this->repository->findById($id);

            if (!$adjustment) {
                throw new Exception('Adjustment not found');
            }

            // Delete associated transactions
            InventoryTransaction::where('transactionable_id', $id)
                ->where('transactionable_type', StockAdjustmentDetail::class)
                ->delete();

            return $this->repository->delete($id);
        });
    }
    /**
     * Dispatch a job to zero out all stock in a specific store
     *
     * @param int $storeId
     * @param int|null $reasonId
     * @param string|null $notes
     * @param bool $forced Whether to use direct transaction zeroing (bypasses aggregate FIFO checks)
     * @return void
     */
    public function zeroStoreStock(int $storeId, ?int $reasonId = null, ?string $notes = null, bool $forced = true): void
    {
        ZeroStoreStockJob::dispatch($storeId, $reasonId, $notes, Auth::id(), $forced);
    }

    /**
     * Logic to zero out stock by closing individual batches (Bypasses aggregate mismatches)
     *
     * @param int $storeId
     * @param int|null $reasonId
     * @param string|null $notes
     * @return int Number of batches closed
     */
    public function processZeroStoreStockDirect(int $storeId, ?int $reasonId = null, ?string $notes = null): int
    {
        $output = new \Symfony\Component\Console\Output\ConsoleOutput();
        $output->writeln("\n<info>==================================================</info>");
        $output->writeln("<info>🚀 تصفير آلي للمخزون - Store ID: {$storeId}</info>");
        $output->writeln("<info>==================================================</info>");

        return DB::transaction(function () use ($storeId, $reasonId, $notes, $output) {
            // Optimize: Only fetch products that actually have a balance in this store
            $productIdsWithStock = InventoryTransaction::where('store_id', $storeId)
                ->where('movement_type', InventoryTransaction::MOVEMENT_IN)
                ->where('remaining_quantity', '>', 0)
                ->distinct()
                ->pluck('product_id')
                ->toArray();

            $inventoryService = new \App\Services\MultiProductsInventoryService(
                null,
                null,
                'all',
                $storeId,
                true // filterOnlyAvailable
            );
            $inventoryService->productIds = $productIdsWithStock;

            // Fetch current stock from report (now only for relevant products)
            $reportResult = $inventoryService->getInventoryReport();
            $reportData = $reportResult['report'] ?? [];
            $totalProducts = count($reportData);

            $output->writeln("<comment>📊 جاري تصفير {$totalProducts} صنف متبقي في المخزون...</comment>");

            if ($totalProducts === 0) {
                $output->writeln("<info>✅ المخزن صفر بالفعل. لا توجد حركات مطلوبة.</info>");
                return 0;
            }

            $closedCount = 0;
            foreach ($reportData as $productUnits) {
                if (empty($productUnits)) continue;

                // Find the unit with the smallest package_size (base unit)
                $baseUnit = collect($productUnits)->sortBy('package_size')->first();

                if ($baseUnit && $baseUnit['remaining_qty'] > 0) {
                    $productId = $baseUnit['product_id'];
                    $qtyToZero = (float)$baseUnit['remaining_qty'];

                    // 1. Create one balancing OUT transaction
                    InventoryTransaction::create([
                        'product_id'            => $productId,
                        'movement_type'         => InventoryTransaction::MOVEMENT_OUT,
                        'quantity'              => $qtyToZero,
                        'unit_id'               => $baseUnit['unit_id'],
                        'package_size'          => $baseUnit['package_size'],
                        'price'                 => $baseUnit['price'] ?? 0,
                        'movement_date'         => now(),
                        'transaction_date'      => now(),
                        'store_id'              => $storeId,
                        'notes'                 => 'تصفير آلي للمخزون',
                        'transactionable_id'    => 0,
                        'transactionable_type'  => 'AutoZero',
                    ]);

                    // 2. Mass-update all previous IN transactions to close them (FIFO integrity)
                    InventoryTransaction::where('store_id', $storeId)
                        ->where('product_id', $productId)
                        ->where('movement_type', InventoryTransaction::MOVEMENT_IN)
                        ->where('remaining_quantity', '>', 0)
                        ->update(['remaining_quantity' => 0]);

                    $closedCount++;
                    if ($closedCount % 50 == 0 || $closedCount == $totalProducts) {
                        $percentage = round(($closedCount / $totalProducts) * 100);
                        $output->writeln("⏳ التقدم: [{$percentage}%] (تم تصفير {$closedCount} من {$totalProducts} صنف)");
                    }
                }
            }

            $output->writeln("<info>==================================================</info>");
            $output->writeln("<info>✅ تم التصفير الآلي للمخزون بنجاح (إجمالي الأصناف: {$closedCount})</info>");
            $output->writeln("<info>==================================================</info>\n");

            return $closedCount;
        });
    }

    /**
     * Actual logic to zero out all stock in a specific store (Original FIFO method)
     *
     * @param int $storeId
     * @param int|null $reasonId
     * @param string|null $notes
     * @return int Number of products zeroed out
     * @throws Exception
     */
    public function processZeroStoreStock(int $storeId, ?int $reasonId = null, ?string $notes = null): int
    {
        $output = new \Symfony\Component\Console\Output\ConsoleOutput();
        $output->writeln("\n<info>==================================================</info>");
        $output->writeln("<info>🚀 Starting stock zeroing for Store ID: {$storeId}</info>");
        $output->writeln("<info>==================================================</info>");

        return DB::transaction(function () use ($storeId, $reasonId, $notes, $output) {
            $inventoryService = new \App\Services\MultiProductsInventoryService(
                null,
                null,
                'all',
                $storeId,
                true // filterOnlyAvailable
            );

            $report = $inventoryService->getInventoryReportWithPagination(5000)['reportData'];
            $totalProducts = count($report);
            $output->writeln("<comment>📊 Found {$totalProducts} products with remaining stock.</comment>");

            if ($totalProducts === 0) {
                $output->writeln("<info>✅ No products with stock found. Store is already empty.</info>");
                return 0;
            }

            $zeroedCount = 0;
            $processedCount = 0;

            foreach ($report as $productData) {
                $processedCount++;
                foreach ($productData as $unitInfo) {
                    $remainingQty = (float)($unitInfo['remaining_qty'] ?? 0);

                    if ($remainingQty > 0) {
                        $this->createManualAdjustment([
                            'product_id'      => $unitInfo['product_id'],
                            'store_id'        => $storeId,
                            'unit_id'         => $unitInfo['unit_id'],
                            'package_size'    => $unitInfo['package_size'],
                            'quantity'        => $remainingQty,
                            'adjustment_type' => 'decrease',
                            'adjustment_date' => now(),
                            'notes'           => $notes ?? 'تصفير آلي بغرض الجرد (Auto-zeroing for inventory purposes)',
                            'reason_id'       => $reasonId,
                        ]);
                        $zeroedCount++;
                    }
                }

                // Show progress percentage
                $percentage = round(($processedCount / $totalProducts) * 100);
                if ($processedCount % 50 == 0 || $processedCount == $totalProducts) {
                    $output->writeln("⏳ Progress: [{$percentage}%] ({$processedCount}/{$totalProducts} products processed)");
                }
            }

            $output->writeln("<info>==================================================</info>");
            $output->writeln("<info>✅ Completed! Total zeroed product-unit records: {$zeroedCount}</info>");
            $output->writeln("<info>==================================================</info>\n");
            return $zeroedCount;
        });
    }
}
