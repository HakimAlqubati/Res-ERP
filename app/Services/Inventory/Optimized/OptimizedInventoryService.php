<?php

namespace App\Services\Inventory\Optimized;

use App\Models\Product;
use App\Models\UnitPrice;
use App\Models\InventoryTransaction;
use App\Services\Inventory\Optimized\DTOs\InventoryFilterDto;
use App\Services\Inventory\Optimized\DTOs\InventoryReportDto;
use App\Services\Inventory\Optimized\Components\InventoryDataLoader;
use App\Services\Inventory\Optimized\Components\InventoryPriceResolver;
use App\Services\Inventory\Optimized\Components\InventoryQueryBuilder;
use App\Services\Inventory\Optimized\Components\InventoryReportBuilder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * OptimizedInventoryService
 * 
 * خدمة المخزون المُحسّنة - بديل لـ MultiProductsInventoryService
 * 
 * ═══════════════════════════════════════════════════════════════════════════════
 * تطبيق مبدأ Single Responsibility Principle (SRP)
 * ═══════════════════════════════════════════════════════════════════════════════
 * 
 * هذا الكلاس يعمل كـ Facade يجمع بين:
 * 
 * 1. InventoryDataLoader     - تحميل البيانات من قاعدة البيانات
 * 2. InventoryQueryBuilder   - بناء الاستعلامات
 * 3. InventoryReportBuilder  - بناء التقارير
 * 4. InventoryPriceResolver  - حل الأسعار
 * 
 * المسؤولية الوحيدة لهذا الكلاس: التنسيق بين المكونات
 * 
 * ═══════════════════════════════════════════════════════════════════════════════
 * التحسينات الرئيسية:
 * ═══════════════════════════════════════════════════════════════════════════════
 * 
 * 1. إزالة N+1 Query Problem: استعلام واحد لجميع المنتجات
 * 2. استخدام DTOs: للـ Type Safety
 * 3. مبدأ SRP: كل كلاس مسؤولية واحدة
 * 4. Subqueries: لا تحميل للذاكرة غير ضروري
 * ═══════════════════════════════════════════════════════════════════════════════
 */
class OptimizedInventoryService
{
    private InventoryFilterDto $filter;
    private InventoryDataLoader $dataLoader;
    private InventoryQueryBuilder $queryBuilder;
    private InventoryReportBuilder $reportBuilder;

    public function __construct(InventoryFilterDto $filter)
    {
        $this->filter = $filter;
        $this->initializeComponents();
    }

    private function initializeComponents(): void
    {
        $this->dataLoader = new InventoryDataLoader($this->filter);
        $this->queryBuilder = new InventoryQueryBuilder($this->filter);
        $this->reportBuilder = new InventoryReportBuilder(
            $this->dataLoader,
            new InventoryPriceResolver()
        );
    }

    // ═══════════════════════════════════════════════════════════════════════════
    // Factory Methods
    // ═══════════════════════════════════════════════════════════════════════════

    /**
     * Factory method للتوافق مع الكود القديم
     */
    public static function make(
        ?int $categoryId = null,
        ?int $productId = null,
        mixed $unitId = 'all',
        ?int $storeId = null,
        bool $filterOnlyAvailable = false
    ): self {
        return new self(
            InventoryFilterDto::fromLegacyParams($categoryId, $productId, $unitId, $storeId, $filterOnlyAvailable)
        );
    }

    // ═══════════════════════════════════════════════════════════════════════════
    // Setters (Fluent Interface)
    // ═══════════════════════════════════════════════════════════════════════════

    public function setProductIds(array $productIds): self
    {
        $this->filter = $this->filter->withProductIds($productIds);
        $this->refreshComponents();
        return $this;
    }

    public function setActive(bool $active = true): self
    {
        $this->filter = $this->filter->withActive($active);
        $this->refreshComponents();
        return $this;
    }

    private function refreshComponents(): void
    {
        $this->dataLoader = new InventoryDataLoader($this->filter);
        $this->queryBuilder->setFilter($this->filter);
    }

    // ═══════════════════════════════════════════════════════════════════════════
    // Public API (Main Methods)
    // ═══════════════════════════════════════════════════════════════════════════

    /**
     * الحصول على تقرير المخزون (مع Pagination افتراضياً)
     * 
     * ═══════════════════════════════════════════════════════════════════════════
     * التحسين: الدالة الافتراضية تستخدم Pagination لضمان أفضل أداء
     * ═══════════════════════════════════════════════════════════════════════════
     * 
     * @param int $perPage عدد العناصر في الصفحة (افتراضي: 15)
     */
    public function getInventoryReport(int $perPage = 15): array
    {
        return $this->getInventoryReportWithPagination($perPage);
    }

    /**
     * تقرير المخزون مع Pagination
     */
    public function getInventoryReportWithPagination(int $perPage = 15): array
    {
        // الحالة 1: منتجات محددة مسبقاً
        if (!empty($this->filter->productIds)) {
            return $this->handleSpecificProducts();
        }

        // الحالة 2: منتج واحد محدد
        if ($this->filter->productId) {
            return $this->handleSingleProduct();
        }

        // الحالة 3: فلترة على المتوفر فقط
        if ($this->filter->filterOnlyAvailable) {
            return $this->handleAvailableOnlyWithPagination($perPage);
        }

        // الحالة 4: Pagination عادي
        return $this->handleNormalPagination($perPage);
    }

    /**
     * الحصول على المخزون لمنتج واحد
     */
    public function getInventoryForProduct(int $productId, array $productIds = []): array
    {
        if (!empty($productIds)) {
            $result = [];
            foreach ($productIds as $id) {
                $result = array_merge($result, $this->getInventoryForProduct($id));
            }
            return $result;
        }

        $this->dataLoader->loadForProducts([$productId]);
        return $this->reportBuilder->buildForSingleProduct($productId);
    }

    /**
     * الحصول على المنتجات تحت الحد الأدنى مع Pagination
     */
    public function getProductsBelowMinimumQuantity(int $perPage = 15, bool $active = false): LengthAwarePaginator
    {
        return $this->getProductsBelowMinimumQuantityWithPagination($perPage, $active);
    }

    /**
     * الحصول على المنتجات تحت الحد الأدنى مع Pagination
     */
    public function getProductsBelowMinimumQuantityWithPagination(int $perPage = 15, bool $active = false): LengthAwarePaginator
    {
        if ($active) {
            $this->setActive(true);
        }

        // جلب الصفحة الحالية فقط
        $report = $this->handleNormalPagination($perPage);
        $lowStock = $this->reportBuilder->filterBelowMinimum($report['reportData'] ?? [], false, true);

        return $this->paginateArray($lowStock, $perPage);
    }

    // ═══════════════════════════════════════════════════════════════════════════
    // Static Helper Methods
    // ═══════════════════════════════════════════════════════════════════════════

    /**
     * الحصول على الكمية المتبقية - Static method
     */
    public static function getRemainingQty(int $productId, int $unitId, int $storeId): float
    {
        $service = self::make(null, $productId, $unitId, $storeId);
        $inventory = $service->getInventoryForProduct($productId);
        return $inventory[0]['remaining_qty'] ?? 0.0;
    }

    /**
     * تقرير سريع لمنتج واحد - Static method
     */
    public static function quickReport(int $storeId, int $productId, ?int $unitId = null): array
    {
        $service = self::make(null, $productId, $unitId, $storeId);
        return [
            'reportData' => [$service->getInventoryForProduct($productId)],
            'pagination' => null,
            'totalPages' => 1,
        ];
    }

    // ═══════════════════════════════════════════════════════════════════════════
    // Unit Price Helper Methods
    // ═══════════════════════════════════════════════════════════════════════════

    public function getProductUnitPrices(int $productId): Collection
    {
        if (!$productId) {
            return collect();
        }

        $product = Product::find($productId);
        if (!$product) {
            return collect();
        }

        return $this->loadUnitPricesForProduct($product);
    }

    public function getLastUnitByPackageSize(int $productId): ?UnitPrice
    {
        return UnitPrice::where('product_id', $productId)
            ->with('unit')
            ->orderByDesc('package_size')
            ->first();
    }

    public function getSmallestUnitByPackageSize(int $productId): ?UnitPrice
    {
        return UnitPrice::where('product_id', $productId)
            ->with('unit')
            ->orderBy('package_size', 'asc')
            ->first();
    }

    // ═══════════════════════════════════════════════════════════════════════════
    // Movement Reports
    // ═══════════════════════════════════════════════════════════════════════════

    public function getInventoryIn(int $productId): array
    {
        return $this->getMovementReport($productId, InventoryTransaction::MOVEMENT_IN);
    }

    public function getInventoryOut(int $productId): array
    {
        return $this->getMovementReport($productId, InventoryTransaction::MOVEMENT_OUT);
    }

    private function getMovementReport(int $productId, string $movementType): array
    {
        $total = $this->queryBuilder->getMovementTotal($productId, $movementType);
        $unitPrices = $this->getProductUnitPrices($productId);
        $product = Product::find($productId);
        $result = [];

        foreach ($unitPrices as $unitPrice) {
            $packageSize = max($unitPrice['package_size'] ?? 1, 1);

            $result[] = [
                'product_id' => $productId,
                'product_name' => $product?->name,
                'unit_id' => $unitPrice['unit_id'],
                'order' => $unitPrice['order'],
                'package_size' => $unitPrice['package_size'],
                'unit_name' => $unitPrice['unit_name'],
                'quantity' => round($total / $packageSize, 4),
                'is_last_unit' => $unitPrice['is_last_unit'],
            ];
        }

        return $result;
    }

    // ═══════════════════════════════════════════════════════════════════════════
    // Pagination Handlers
    // ═══════════════════════════════════════════════════════════════════════════

    private function handleSpecificProducts(): array
    {
        $productIds = $this->filter->productIds;
        $this->dataLoader->loadForProducts($productIds);
        $report = $this->reportBuilder->buildForProducts($productIds);

        return [
            'reportData' => $report,
            'pagination' => null,
            'totalPages' => 1,
        ];
    }

    private function handleSingleProduct(): array
    {
        return [
            'reportData' => [$this->getInventoryForProduct($this->filter->productId)],
            'pagination' => null,
            'totalPages' => 1,
        ];
    }

    private function handleAvailableOnlyWithPagination(int $perPage): array
    {
        $availableProductIdsQuery = $this->queryBuilder->getAvailableProductIdsQuery();

        $totalCount = $availableProductIdsQuery->count();

        $currentPage = (int) request()->get('page', 1);
        $offset = ($currentPage - 1) * $perPage;

        $paginatedProductIds = $availableProductIdsQuery
            ->offset($offset)
            ->limit($perPage)
            ->pluck('product_id')
            ->toArray();

        if (empty($paginatedProductIds)) {
            return [
                'reportData' => [],
                'pagination' => $this->createEmptyPaginator($perPage, $currentPage),
                'totalPages' => 0,
            ];
        }

        $this->dataLoader->reset()->loadForProducts($paginatedProductIds);
        $report = $this->reportBuilder->buildForProducts($paginatedProductIds);

        $pagination = new LengthAwarePaginator(
            $report,
            $totalCount,
            $perPage,
            $currentPage,
            ['path' => request()->url(), 'query' => request()->query()]
        );

        return [
            'reportData' => $report,
            'pagination' => $pagination,
            'totalPages' => $pagination->lastPage(),
        ];
    }

    private function handleNormalPagination(int $perPage): array
    {
        $query = $this->queryBuilder->buildProductQuery();
        $products = $query->paginate($perPage);
        $productIds = $products->pluck('id')->toArray();

        $this->dataLoader->loadForProducts($productIds);
        $report = $this->reportBuilder->buildForProducts($productIds);

        return [
            'reportData' => $report,
            'pagination' => $products,
            'totalPages' => $products->lastPage(),
        ];
    }

    // ═══════════════════════════════════════════════════════════════════════════
    // Helper Methods
    // ═══════════════════════════════════════════════════════════════════════════

    private function loadUnitPricesForProduct(Product $product): Collection
    {
        $query = $product->reportUnitPrices()
            ->orderBy('order', 'asc')
            ->with('unit');

        if ($this->filter->hasUnitFilter()) {
            if (is_array($this->filter->unitId)) {
                $query->whereIn('unit_id', $this->filter->unitId);
            } elseif (is_numeric($this->filter->unitId)) {
                $query->where('unit_id', $this->filter->unitId);
            }
        }

        $unitPrices = $query->get();
        $maxOrder = $unitPrices->max('order');
        $maxPackageSize = $unitPrices->max('package_size');

        return $unitPrices->map(function ($unitPrice) use ($maxOrder, $maxPackageSize, $product) {
            return [
                'unit_id' => $unitPrice->unit_id,
                'order' => $unitPrice->order,
                'package_size' => $unitPrice->package_size,
                'unit_name' => $unitPrice->unit->name ?? 'Unknown',
                'minimum_quantity' => $unitPrice->order == $maxOrder ? ($product->minimum_stock_qty ?? 0) : 0,
                'is_last_unit' => $unitPrice->order == $maxOrder,
                'is_largest_unit' => $unitPrice->package_size == $maxPackageSize,
                'price' => $unitPrice->price,
                'is_fractional' => $unitPrice->unit->is_fractional ?? true,
            ];
        });
    }

    private function paginateArray(array $items, int $perPage): LengthAwarePaginator
    {
        $currentPage = request()->get('page', 1);
        $collection = collect($items);
        $pagedData = $collection->slice(($currentPage - 1) * $perPage, $perPage)->values();

        return new LengthAwarePaginator(
            $pagedData,
            $collection->count(),
            $perPage,
            $currentPage,
            ['path' => request()->url(), 'query' => request()->query()]
        );
    }

    private function createEmptyPaginator(int $perPage, int $currentPage): LengthAwarePaginator
    {
        return new LengthAwarePaginator(
            [],
            0,
            $perPage,
            $currentPage,
            ['path' => request()->url(), 'query' => request()->query()]
        );
    }

    // ═══════════════════════════════════════════════════════════════════════════
    // Component Accessors (for testing/extension)
    // ═══════════════════════════════════════════════════════════════════════════

    public function getDataLoader(): InventoryDataLoader
    {
        return $this->dataLoader;
    }

    public function getQueryBuilder(): InventoryQueryBuilder
    {
        return $this->queryBuilder;
    }

    public function getReportBuilder(): InventoryReportBuilder
    {
        return $this->reportBuilder;
    }

    public function getFilter(): InventoryFilterDto
    {
        return $this->filter;
    }
}
