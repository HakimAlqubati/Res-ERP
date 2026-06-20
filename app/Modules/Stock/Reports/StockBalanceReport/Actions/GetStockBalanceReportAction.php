<?php

declare(strict_types=1);

namespace App\Modules\Stock\Reports\StockBalanceReport\Actions;

use App\Modules\Stock\Reports\StockBalanceReport\Contracts\StockBalanceRepositoryInterface;
use App\Modules\Stock\Reports\StockBalanceReport\DataTransferObjects\StockBalanceFilterDTO;
use App\Modules\Stock\Reports\StockBalanceReport\Resources\StockBalanceResource;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

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
     */
    public function execute(StockBalanceFilterDTO $filters): AnonymousResourceCollection
    {
        // 1. يمكننا هنا إضافة أي منطق أعمال (Business Logic) قبل الاستعلام
        // مثلاً: التأكد من صلاحيات المستخدم، أو تسجيل الحدث (Logging).

        // 2. جلب البيانات من المستودع
        $rawReport = $this->repository->getBalances($filters);

        return StockBalanceResource::collection($rawReport);
    }
}
