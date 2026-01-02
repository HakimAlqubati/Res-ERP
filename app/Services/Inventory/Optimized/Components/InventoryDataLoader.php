<?php

namespace App\Services\Inventory\Optimized\Components;

use App\Models\Product;
use App\Models\UnitPrice;
use App\Models\InventoryTransaction;
use App\Services\Inventory\Optimized\DTOs\InventoryFilterDto;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * InventoryDataLoader
 * 
 * مسؤول عن تحميل البيانات من قاعدة البيانات بشكل مجمّع (Bulk Loading)
 * 
 * المسؤولية الوحيدة: جلب البيانات الخام من قاعدة البيانات
 */
class InventoryDataLoader
{
    private InventoryFilterDto $filter;

    /** @var Collection<int, Product> */
    private Collection $products;

    /** @var Collection<int, Collection> */
    private Collection $unitPrices;

    /** @var Collection<int, object> */
    private Collection $inventoryTotals;

    /** @var Collection<int, Collection> */
    private Collection $lastTransactionPrices;

    private bool $isLoaded = false;

    public function __construct(InventoryFilterDto $filter)
    {
        $this->filter = $filter;
        $this->initializeCollections();
    }

    private function initializeCollections(): void
    {
        $this->products = collect();
        $this->unitPrices = collect();
        $this->inventoryTotals = collect();
        $this->lastTransactionPrices = collect();
    }

    /**
     * تحميل جميع البيانات المطلوبة لقائمة المنتجات
     */
    public function loadForProducts(array $productIds): self
    {
        if (empty($productIds) || $this->isLoaded) {
            return $this;
        }

        $this->loadProducts($productIds);
        $this->loadUnitPrices($productIds);
        $this->loadInventoryTotals($productIds);
        $this->loadLastTransactionPrices($productIds);

        $this->isLoaded = true;
        return $this;
    }

    /**
     * إعادة تعيين البيانات المحملة
     */
    public function reset(): self
    {
        $this->initializeCollections();
        $this->isLoaded = false;
        return $this;
    }

    // ═══════════════════════════════════════════════════════════════════════════
    // Getters
    // ═══════════════════════════════════════════════════════════════════════════

    public function getProducts(): Collection
    {
        return $this->products;
    }

    public function getProduct(int $productId): ?Product
    {
        return $this->products[$productId] ?? null;
    }

    public function getUnitPrices(): Collection
    {
        return $this->unitPrices;
    }

    public function getUnitPricesForProduct(int $productId): Collection
    {
        return $this->unitPrices[$productId] ?? collect();
    }

    public function getInventoryTotals(): Collection
    {
        return $this->inventoryTotals;
    }

    public function getInventoryTotalsForProduct(int $productId): ?object
    {
        return $this->inventoryTotals[$productId] ?? null;
    }

    public function getLastTransactionPrices(): Collection
    {
        return $this->lastTransactionPrices;
    }

    public function getTransactionPricesForProduct(int $productId): Collection
    {
        return $this->lastTransactionPrices[$productId] ?? collect();
    }

    public function isLoaded(): bool
    {
        return $this->isLoaded;
    }

    // ═══════════════════════════════════════════════════════════════════════════
    // Private Loading Methods
    // ═══════════════════════════════════════════════════════════════════════════

    private function loadProducts(array $productIds): void
    {
        $this->products = Product::whereIn('id', $productIds)
            ->get()
            ->keyBy('id');
    }

    private function loadUnitPrices(array $productIds): void
    {
        $query = UnitPrice::whereIn('product_id', $productIds)
            ->with('unit')
            ->orderBy('order', 'asc');

        if ($this->filter->hasUnitFilter()) {
            if (is_array($this->filter->unitId)) {
                $query->whereIn('unit_id', $this->filter->unitId);
            } elseif (is_numeric($this->filter->unitId)) {
                $query->where('unit_id', $this->filter->unitId);
            }
        }

        $this->unitPrices = $query->get()->groupBy('product_id');
    }

    /**
     * تحميل إجماليات المخزون باستعلام واحد محسّن
     */
    private function loadInventoryTotals(array $productIds): void
    {
        $this->inventoryTotals = DB::table('inventory_transactions')
            ->whereIn('product_id', $productIds)
            ->where('store_id', $this->filter->storeId)
            ->whereNull('deleted_at')
            ->groupBy('product_id')
            ->selectRaw('
                product_id,
                COALESCE(SUM(CASE WHEN movement_type = ? THEN quantity * package_size ELSE 0 END), 0) as total_in,
                COALESCE(SUM(CASE WHEN movement_type = ? THEN quantity * package_size ELSE 0 END), 0) as total_out,
                COALESCE(SUM(CASE WHEN movement_type = ? THEN IFNULL(base_quantity, quantity * package_size) ELSE 0 END), 0) as total_base_in,
                COALESCE(SUM(CASE WHEN movement_type = ? THEN IFNULL(base_quantity, quantity * package_size) ELSE 0 END), 0) as total_base_out
            ', [
                InventoryTransaction::MOVEMENT_IN,
                InventoryTransaction::MOVEMENT_OUT,
                InventoryTransaction::MOVEMENT_IN,
                InventoryTransaction::MOVEMENT_OUT,
            ])
            ->get()
            ->keyBy('product_id');
    }

    /**
     * تحميل آخر أسعار من حركات المخزون
     */
    private function loadLastTransactionPrices(array $productIds): void
    {
        $lastIds = DB::table('inventory_transactions')
            ->whereIn('product_id', $productIds)
            ->where('movement_type', InventoryTransaction::MOVEMENT_IN)
            ->whereNull('deleted_at')
            ->groupBy('product_id', 'unit_id')
            ->selectRaw('product_id, unit_id, MAX(id) as last_id')
            ->get();

        if ($lastIds->isEmpty()) {
            $this->lastTransactionPrices = collect();
            return;
        }

        $transactions = DB::table('inventory_transactions')
            ->whereIn('id', $lastIds->pluck('last_id')->toArray())
            ->get(['id', 'product_id', 'unit_id', 'price', 'store_id']);

        $this->lastTransactionPrices = $transactions->groupBy('product_id')
            ->map(fn($items) => $items->keyBy('unit_id'));
    }
}
