<?php

declare(strict_types=1);

namespace App\Modules\Stock\Reports\StockBalanceReport\Actions;

use App\Modules\Stock\Reports\StockBalanceReport\Contracts\StockBalanceRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

final readonly class GetLowStockProductsAction
{
    public function __construct(
        private StockBalanceRepositoryInterface $repository
    ) {}

    /**
     * تنفيذ الإجراء لجلب النواقص.
     */
    public function execute(int $storeId, ?int $perPage = 15): Collection|LengthAwarePaginator
    {
        // 1. نطلب من المستودع جلب المنتجات التي رصيدها <= الحد الأدنى
        // تذكر: الفلترة ستتم في قاعدة البيانات وليس في الـ PHP!
        $lowStockData = $this->repository->getLowStockProducts($storeId, $perPage);

        // 2. إعادة البيانات جاهزة للعرض
        return $lowStockData;
    }
}