<?php

namespace App\Services\Inventory\StockInventory;

use App\DTOs\Inventory\StockInventory\StockInventoryDto;
use App\Models\StockInventory;
use App\Repositories\Inventory\StockInventory\Contracts\StockInventoryRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Exception;

class StockInventoryService
{
    /**
     * @var StockInventoryRepositoryInterface
     */
    protected StockInventoryRepositoryInterface $repository;

    /**
     * StockInventoryService constructor.
     *
     * @param StockInventoryRepositoryInterface $repository
     */
    public function __construct(StockInventoryRepositoryInterface $repository)
    {
        $this->repository = $repository;
    }

    /**
     * Get paginated stock inventories
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
     * Get single stock inventory by ID
     *
     * @param int $id
     * @return StockInventory|null
     */
    public function getById(int $id): ?StockInventory
    {
        return $this->repository->findById($id, [
            'store',
            'responsibleUser',
            'creator',
            'details.product',
            'details.unit'
        ]);
    }

    /**
     * Create a new stock inventory with details
     *
     * @param StockInventoryDto $dto
     * @return StockInventory
     * @throws Exception
     */
    public function create(StockInventoryDto $dto): StockInventory
    {
        return DB::transaction(function () use ($dto) {
            // Create main inventory record
            $inventory = $this->repository->create($dto->toArray());

            // Create details if provided
            if ($dto->hasDetails()) {
                $this->repository->createDetails($inventory->id, $dto->getDetails());
            }

            // Reload with relationships
            return $this->repository->findById($inventory->id, [
                'store',
                'responsibleUser',
                'creator',
                'details.product',
                'details.unit'
            ]);
        });
    }

    /**
     * Update an existing stock inventory
     *
     * @param int $id
     * @param array $data
     * @return StockInventory
     * @throws Exception
     */
    public function update(int $id, array $data): StockInventory
    {
        return DB::transaction(function () use ($id, $data) {
            // Get inventory
            $inventory = $this->repository->findById($id);

            if (!$inventory) {
                throw new Exception('Stock inventory not found');
            }

            // Check if finalized
            if ($inventory->finalized) {
                throw new Exception('Cannot update finalized inventory');
            }

            // Update main record
            $mainData = collect($data)->only([
                'inventory_date',
                'store_id',
                'responsible_user_id',
                'finalized'
            ])->toArray();

            if (!empty($mainData)) {
                $inventory = $this->repository->update($id, $mainData);
            }

            // Update details if provided
            if (isset($data['details']) && is_array($data['details'])) {
                $this->repository->deleteDetails($id);
                $this->repository->createDetails($id, $data['details']);
            }

            // Reload with relationships
            return $this->repository->findById($id, [
                'store',
                'responsibleUser',
                'creator',
                'details.product',
                'details.unit'
            ]);
        });
    }

    /**
     * Delete a stock inventory
     *
     * @param int $id
     * @return bool
     * @throws Exception
     */
    public function delete(int $id): bool
    {
        $inventory = $this->repository->findById($id);

        if (!$inventory) {
            throw new Exception('Stock inventory not found');
        }

        if ($inventory->finalized) {
            throw new Exception('Cannot delete finalized inventory');
        }

        return $this->repository->delete($id);
    }

    /**
     * Finalize a stock inventory
     *
     * @param int $id
     * @return StockInventory
     * @throws Exception
     */
    public function finalize(int $id): StockInventory
    {
        $inventory = $this->repository->findById($id);

        if (!$inventory) {
            throw new Exception('Stock inventory not found');
        }

        if ($inventory->finalized) {
            throw new Exception('Stock inventory is already finalized');
        }

        $finalizedInventory = $this->repository->finalize($id);

        // Reload with relationships
        return $this->repository->findById($finalizedInventory->id, [
            'store',
            'responsibleUser',
            'creator',
            'details.product',
            'details.unit'
        ]);
    }

    /**
     * Check if inventory can be modified
     *
     * @param int $id
     * @return bool
     */
    public function canModify(int $id): bool
    {
        $inventory = $this->repository->findById($id);

        return $inventory && !$inventory->finalized;
    }
}
