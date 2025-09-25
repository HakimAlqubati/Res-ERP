<?php
// File: app/Services/Inventory/Repositories/EloquentInventoryRepository.php
namespace App\Services\Inventory\Repositories;

use App\Services\Inventory\Contracts\InventoryRepositoryInterface;
use App\Services\Inventory\Dto\InventoryFiltersDTO;
use Illuminate\Support\Facades\DB;

final class EloquentInventoryRepository implements InventoryRepositoryInterface
{
    protected $pagination = null;

    public function fetchMovements(InventoryFiltersDTO $f): array
    {
        $q = DB::table('inventory_transactions as it')
            ->selectRaw("
            it.product_id,
            it.unit_id,
            it.package_size,
            it.store_id,
            it.movement_type as movement,
            SUM(it.quantity)                                        as qty,
            SUM(it.quantity * COALESCE(NULLIF(it.package_size,0),1)) as qty_smallest_unit,
            MAX(it.movement_date)                                    as moved_at
        ")
            ->whereNull('it.deleted_at')
            ->where('it.store_id', $f->storeId);

        if (!empty($f->productIds)) {
            $q->whereIn('it.product_id', $f->productIds);
        }
        if (!empty($f->categoryId)) {
            $q->join('products as p', 'p.id', '=', 'it.product_id')
                ->where('p.category_id', $f->categoryId);
        }
        if (!empty($f->dateFrom)) {
            $q->whereDate('it.movement_date', '>=', $f->dateFrom);
        }
        if (!empty($f->dateTo)) {
            $q->whereDate('it.movement_date', '<=', $f->dateTo);
        }

        // ملاحظة: الآن نقسّم حسب package_size أيضاً
        $q->groupBy('it.product_id', 'it.unit_id', 'it.package_size', 'it.store_id', 'it.movement_type')
            ->orderBy('it.product_id')
            ->orderBy('it.store_id')
            ->orderBy('it.unit_id')
            ->orderBy('it.package_size');

            // dd($q->get());
        if (!empty($f->perPage)) {
            $p = $q->paginate($f->perPage, ['*'], 'page', (int) ($f->page ?? 1));
            $this->pagination = $p;
            return array_map(fn($row) => (array) $row, $p->items());
        }

        return array_map(fn($row) => (array) $row, $q->get()->toArray());
    }


    public function pagination()
    {
        return $this->pagination;
    }
}
