<?php

declare(strict_types=1);

namespace App\Modules\Stock\Reports\StockBalanceReport\Actions;

use App\Modules\Stock\Reports\StockBalanceReport\Contracts\StockBalanceRepositoryInterface;
use App\Modules\Stock\Reports\StockBalanceReport\DataTransferObjects\StockBalanceFilterDTO;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

final readonly class GetStockBalanceReportAction
{
    /**
     * نعتمد على الواجهة (Interface) بدلاً من الكلاس المباشر.
     */
    public function __construct(
        private StockBalanceRepositoryInterface $repository
    ) {}

    /**
     * تنفيذ الإجراء لجلب التقرير.
     * * @param StockBalanceFilterDTO $filters
     * @return Collection|LengthAwarePaginator
     */
    public function execute(StockBalanceFilterDTO $filters): Collection|LengthAwarePaginator
    {
        // 1. يمكننا هنا إضافة أي منطق أعمال (Business Logic) قبل الاستعلام
        // مثلاً: التأكد من صلاحيات المستخدم، أو تسجيل الحدث (Logging).

        // 2. جلب البيانات من المستودع
        $reportData = $this->repository->getBalances($filters);

        // 3. إعادة البيانات بصيغتها النهائية
        return $reportData;
    }
}