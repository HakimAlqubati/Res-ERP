<?php

declare(strict_types=1);

namespace App\Modules\Stock\Reports\StockBalanceReport\Contracts;

use App\Modules\Stock\Reports\StockBalanceReport\DataTransferObjects\StockBalanceFilterDTO;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

interface StockBalanceReportInterface
{
    /**
     * توليد تقرير أرصدة المخزون بناءً على الفلاتر.
     * * @param StockBalanceFilterDTO $filters
     * @return Collection|LengthAwarePaginator  // تعيد مجموعة عادية، أو مقسمة لصفحات
     */
    public function generate(StockBalanceFilterDTO $filters): Collection|LengthAwarePaginator;
}