<?php

declare(strict_types=1);

namespace App\Modules\Stock\Reports\FifoBatchReports\Contracts;

use App\Modules\Stock\Reports\FifoBatchReports\DataTransferObjects\StockBatchFilterDTO;
use App\Modules\Stock\Reports\FifoBatchReports\DataTransferObjects\StockBatchReportResult;

interface GetAvailableStockBatchesQueryInterface
{
    /**
     * تنفيذ استعلام جلب باتشات المخزون المتوفرة بناءً على الفلاتر.
     */
    public function execute(StockBatchFilterDTO $filters): StockBatchReportResult;
}
