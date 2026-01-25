<?php

namespace App\Repositories\Inventory\StockAdjustment\Contracts;

use App\Models\StockAdjustmentDetail;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface StockAdjustmentRepositoryInterface
{
    /**
     * Get paginated stock adjustments (details) with filters
     *
     * @param array $filters
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getPaginated(array $filters = [], int $perPage = 15): LengthAwarePaginator;

    /**
     * Find stock adjustment detail by ID
     *
     * @param int $id
     * @param array $relations
     * @return StockAdjustmentDetail|null
     */
    public function findById(int $id, array $relations = []): ?StockAdjustmentDetail;

    /**
     * Create a new stock adjustment detail
     *
     * @param array $data
     * @return StockAdjustmentDetail
     */
    public function create(array $data): StockAdjustmentDetail;

    /**
     * Update stock adjustment detail
     *
     * @param int $id
     * @param array $data
     * @return StockAdjustmentDetail
     */
    public function update(int $id, array $data): StockAdjustmentDetail;

    /**
     * Delete stock adjustment detail
     *
     * @param int $id
     * @return bool
     */
    public function delete(int $id): bool;
}
