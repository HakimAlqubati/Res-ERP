<?php

namespace App\Repositories\Inventory\StockInventory\Contracts;

use App\Models\StockInventory;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface StockInventoryRepositoryInterface
{
    /**
     * Get paginated stock inventories with filters
     *
     * @param array $filters
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getPaginated(array $filters = [], int $perPage = 15): LengthAwarePaginator;

    /**
     * Find stock inventory by ID
     *
     * @param int $id
     * @param array $relations
     * @return StockInventory|null
     */
    public function findById(int $id, array $relations = []): ?StockInventory;

    /**
     * Create a new stock inventory
     *
     * @param array $data
     * @return StockInventory
     */
    public function create(array $data): StockInventory;

    /**
     * Update stock inventory
     *
     * @param int $id
     * @param array $data
     * @return StockInventory
     */
    public function update(int $id, array $data): StockInventory;

    /**
     * Delete stock inventory (soft delete)
     *
     * @param int $id
     * @return bool
     */
    public function delete(int $id): bool;

    /**
     * Finalize a stock inventory
     *
     * @param int $id
     * @return StockInventory
     */
    public function finalize(int $id): StockInventory;

    /**
     * Create inventory details
     *
     * @param int $stockInventoryId
     * @param array $details
     * @return void
     */
    public function createDetails(int $stockInventoryId, array $details): void;

    /**
     * Delete all details for an inventory
     *
     * @param int $stockInventoryId
     * @return void
     */
    public function deleteDetails(int $stockInventoryId): void;
}
