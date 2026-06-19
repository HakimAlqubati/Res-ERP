<?php

declare(strict_types=1);

namespace App\Modules\Stock\Reports\StockBalanceReport\Contracts;

use App\Modules\Stock\Reports\StockBalanceReport\DataTransferObjects\StockBalanceFilterDTO;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

interface StockBalanceRepositoryInterface
{
    /**
     * جلب أرصدة المخزون بناءً على الفلاتر المحددة.
     */
    public function getBalances(StockBalanceFilterDTO $filters): Collection|LengthAwarePaginator;
    /**
     * 2. جلب الرصيد الخام لمنتج واحد في مخزن معين.
     */
    public function getSingleProductBalance(int $productId, int $storeId): ?object;

    /**
     * 3. جلب المنتجات التي وصل رصيدها للحد الأدنى (تقرير النواقص).
     */
    public function getLowStockProducts(int $storeId, ?int $perPage = 15): Collection|LengthAwarePaginator;
}