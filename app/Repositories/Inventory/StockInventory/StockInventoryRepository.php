<?php

namespace App\Repositories\Inventory\StockInventory;

use App\Models\StockInventory;
use App\Models\StockInventoryDetail;
use App\Repositories\Inventory\StockInventory\Contracts\StockInventoryRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class StockInventoryRepository implements StockInventoryRepositoryInterface
{
    /**
     * @var StockInventory
     */
    protected StockInventory $model;

    /**
     * StockInventoryRepository constructor.
     *
     * @param StockInventory $model
     */
    public function __construct(StockInventory $model)
    {
        $this->model = $model;
    }

    /**
     * Get paginated stock inventories with filters
     *
     * @param array $filters
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getPaginated(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = $this->model->query();

        // Load basic relationships (always)
        $query->with(['store', 'responsibleUser', 'creator']);

        // Load details only if explicitly requested
        if (!empty($filters['include_details']) && $filters['include_details'] == true) {
            $query->with(['details.product', 'details.unit']);
        }

        // Apply filters
        if (!empty($filters['store_id'])) {
            $query->where('store_id', $filters['store_id']);
        }

        if (isset($filters['finalized'])) {
            $query->where('finalized', $filters['finalized']);
        }

        if (!empty($filters['date_from'])) {
            $query->where('inventory_date', '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $query->where('inventory_date', '<=', $filters['date_to']);
        }

        if (!empty($filters['responsible_user_id'])) {
            $query->where('responsible_user_id', $filters['responsible_user_id']);
        }

        // Sorting
        $sortBy = $filters['sort_by'] ?? 'inventory_date';
        $sortDirection = $filters['sort_direction'] ?? 'desc';
        $query->orderBy($sortBy, $sortDirection);

        return $query->paginate($perPage);
    }

    /**
     * Find stock inventory by ID
     *
     * @param int $id
     * @param array $relations
     * @return StockInventory|null
     */
    public function findById(int $id, array $relations = []): ?StockInventory
    {
        $query = $this->model->query();

        if (!empty($relations)) {
            $query->with($relations);
        }

        return $query->find($id);
    }

    /**
     * Create a new stock inventory
     *
     * @param array $data
     * @return StockInventory
     */
    public function create(array $data): StockInventory
    {
        return $this->model->create($data);
    }

    /**
     * Update stock inventory
     *
     * @param int $id
     * @param array $data
     * @return StockInventory
     */
    public function update(int $id, array $data): StockInventory
    {
        $inventory = $this->model->findOrFail($id);
        $inventory->update($data);

        return $inventory->fresh();
    }

    /**
     * Delete stock inventory (soft delete)
     *
     * @param int $id
     * @return bool
     */
    public function delete(int $id): bool
    {
        $inventory = $this->model->findOrFail($id);

        return $inventory->delete();
    }

    /**
     * Finalize a stock inventory
     *
     * @param int $id
     * @return StockInventory
     */
    public function finalize(int $id): StockInventory
    {
        $inventory = $this->model->findOrFail($id);
        $inventory->update(['finalized' => true]);

        return $inventory->fresh();
    }

    /**
     * Create inventory details
     *
     * @param int $stockInventoryId
     * @param array $details
     * @return void
     */
    public function createDetails(int $stockInventoryId, array $details): void
    {
        foreach ($details as $detail) {
            $systemQty = $detail['system_quantity'] ?? 0;
            $physicalQty = $detail['physical_quantity'];
            $difference = $physicalQty - $systemQty;

            StockInventoryDetail::create([
                'stock_inventory_id' => $stockInventoryId,
                'product_id' => $detail['product_id'],
                'unit_id' => $detail['unit_id'],
                'system_quantity' => $systemQty,
                'physical_quantity' => $physicalQty,
                'difference' => $difference,
                'package_size' => $detail['package_size'],
                'is_adjustmented' => $detail['is_adjustmented'] ?? false,
            ]);
        }
    }

    /**
     * Delete all details for an inventory
     *
     * @param int $stockInventoryId
     * @return void
     */
    public function deleteDetails(int $stockInventoryId): void
    {
        StockInventoryDetail::where('stock_inventory_id', $stockInventoryId)->delete();
    }
}
