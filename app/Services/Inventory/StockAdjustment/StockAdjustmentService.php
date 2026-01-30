<?php

namespace App\Services\Inventory\StockAdjustment;

use App\Models\InventoryTransaction;
use App\Models\StockAdjustmentDetail;
use App\Models\StockInventory;
use App\Repositories\Inventory\StockAdjustment\Contracts\StockAdjustmentRepositoryInterface;
use App\Services\FifoMethodService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
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
}
