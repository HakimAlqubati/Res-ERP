<?php

namespace App\Repositories\Inventory\StockAdjustment;

use App\Models\StockAdjustmentDetail;
use App\Repositories\Inventory\StockAdjustment\Contracts\StockAdjustmentRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class StockAdjustmentRepository implements StockAdjustmentRepositoryInterface
{
    /**
     * @var StockAdjustmentDetail
     */
    protected StockAdjustmentDetail $model;

    /**
     * StockAdjustmentRepository constructor.
     *
     * @param StockAdjustmentDetail $model
     */
    public function __construct(StockAdjustmentDetail $model)
    {
        $this->model = $model;
    }

    /**
     * Get paginated stock adjustments with filters
     *
     * @param array $filters
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getPaginated(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = $this->model->query();

        // Load basic relationships
        $query->with(['store', 'product', 'unit', 'createdBy']);

        // Apply filters
        if (!empty($filters['store_id'])) {
            $query->where('store_id', $filters['store_id']);
        }

        if (!empty($filters['product_id'])) {
            $query->where('product_id', $filters['product_id']);
        }

        if (!empty($filters['adjustment_type'])) {
            $query->where('adjustment_type', $filters['adjustment_type']);
        }

        if (!empty($filters['date_from'])) {
            $query->where('adjustment_date', '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $query->where('adjustment_date', '<=', $filters['date_to']);
        }

        if (!empty($filters['source_id'])) {
            $query->where('source_id', $filters['source_id']);
        }

        if (!empty($filters['source_type'])) {
            $query->where('source_type', $filters['source_type']);
        }

        // Sorting
        $sortBy = $filters['sort_by'] ?? 'adjustment_date';
        $sortDirection = $filters['sort_direction'] ?? 'desc';
        $query->orderBy($sortBy, $sortDirection);

        return $query->paginate($perPage);
    }

    /**
     * Find stock adjustment detail by ID
     *
     * @param int $id
     * @param array $relations
     * @return StockAdjustmentDetail|null
     */
    public function findById(int $id, array $relations = []): ?StockAdjustmentDetail
    {
        $query = $this->model->query();

        if (!empty($relations)) {
            $query->with($relations);
        }

        return $query->find($id);
    }

    /**
     * Create a new stock adjustment detail
     *
     * @param array $data
     * @return StockAdjustmentDetail
     */
    public function create(array $data): StockAdjustmentDetail
    {
        return $this->model->create($data);
    }

    /**
     * Update stock adjustment detail
     *
     * @param int $id
     * @param array $data
     * @return StockAdjustmentDetail
     */
    public function update(int $id, array $data): StockAdjustmentDetail
    {
        $adjustmentDetail = $this->model->findOrFail($id);
        $adjustmentDetail->update($data);

        return $adjustmentDetail->fresh();
    }

    /**
     * Delete stock adjustment detail
     *
     * @param int $id
     * @return bool
     */
    public function delete(int $id): bool
    {
        $adjustmentDetail = $this->model->findOrFail($id);
        return $adjustmentDetail->delete();
    }
}
