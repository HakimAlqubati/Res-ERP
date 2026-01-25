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
                $diff = (float) $detail->physical_quantity - (float) $detail->system_quantity;

                if ($diff == 0) continue;

                $type = $diff > 0 ? 'increase' : 'decrease';

                $adjustmentData = [
                    'product_id' => $detail->product_id,
                    'unit_id' => $detail['unit_id'],
                    'package_size' => $detail['package_size'],
                    'quantity' => abs($diff),
                    'adjustment_type' => $type,
                    'adjustment_date' => $inventory->inventory_date,
                    'store_id' => $inventory->store_id,
                    'notes' => "Automatic adjustment from Stock Inventory #{$inventory->id}",
                    'source_id' => $inventory->id,
                    'source_type' => StockInventory::class,
                    'created_by' => auth()->id() ?? $inventory->created_by,
                ];

                $adjustmentRecord = $this->repository->create($adjustmentData);

                // Update detail to mark it as adjusted
                $detail->update(['is_adjustmented' => true]);

                // Create Inventory Transaction
                $this->createInventoryTransaction($adjustmentRecord);
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
        $notes = $adjustment->notes ?? "Stock adjustment ({$adjustment->adjustment_type}) ID: {$adjustment->id}";

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
