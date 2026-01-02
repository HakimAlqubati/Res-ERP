<?php

namespace App\Services\Inventory\Optimized\Components;

use App\Models\Product;
use App\Models\InventoryTransaction;
use App\Services\Inventory\Optimized\DTOs\InventoryFilterDto;
use Illuminate\Support\Facades\DB;

/**
 * InventoryQueryBuilder
 * 
 * مسؤول عن بناء استعلامات قاعدة البيانات
 * 
 * المسؤولية الوحيدة: بناء الاستعلامات مع الفلاتر المطلوبة
 */
class InventoryQueryBuilder
{
    private InventoryFilterDto $filter;

    public function __construct(InventoryFilterDto $filter)
    {
        $this->filter = $filter;
    }

    /**
     * بناء استعلام المنتجات الأساسي
     */
    public function buildProductQuery()
    {
        $query = Product::query();

        if ($this->filter->isActive) {
            $query->where('active', true);
        }

        if ($this->filter->categoryId) {
            $query->where('category_id', $this->filter->categoryId);
        }

        return $query;
    }

    /**
     * الحصول على IDs المنتجات المستهدفة
     */
    public function resolveProductIds(): array
    {
        if (!empty($this->filter->productIds)) {
            return $this->filter->productIds;
        }

        if ($this->filter->productId) {
            return [$this->filter->productId];
        }

        return $this->buildProductQuery()->pluck('id')->toArray();
    }

    /**
     * استعلام المنتجات المتوفرة فقط (باستخدام Subquery)
     * 
     * التحسين: يستخدم Subquery بدلاً من تحميل IDs للذاكرة
     */
    public function getAvailableProductIdsQuery()
    {
        // بناء Subquery للمنتجات (لا ينفذ، فقط يُبنى)
        $productSubQuery = $this->buildProductQuery()->select('id');

        return DB::table('inventory_transactions')
            ->whereIn('product_id', $productSubQuery)
            ->where('store_id', $this->filter->storeId)
            ->whereNull('deleted_at')
            ->groupBy('product_id')
            ->havingRaw('
                COALESCE(SUM(CASE WHEN movement_type = ? THEN quantity * package_size ELSE 0 END), 0) -
                COALESCE(SUM(CASE WHEN movement_type = ? THEN quantity * package_size ELSE 0 END), 0) > 0
            ', [
                InventoryTransaction::MOVEMENT_IN,
                InventoryTransaction::MOVEMENT_OUT,
            ])
            ->select('product_id')
            ->orderBy('product_id');
    }

    /**
     * حساب إجمالي حركات معينة لمنتج
     */
    public function getMovementTotal(int $productId, string $movementType): float
    {
        return (float) DB::table('inventory_transactions')
            ->where('product_id', $productId)
            ->where('movement_type', $movementType)
            ->whereNull('deleted_at')
            ->sum(DB::raw('quantity * package_size'));
    }

    /**
     * تحديث الفلتر
     */
    public function setFilter(InventoryFilterDto $filter): self
    {
        $this->filter = $filter;
        return $this;
    }

    public function getFilter(): InventoryFilterDto
    {
        return $this->filter;
    }
}
