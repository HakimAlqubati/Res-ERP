<?php

namespace App\Modules\Stock\Reports\OrderTransfersReports\Interfaces;

use App\Modules\Stock\Reports\OrderTransfersReports\DTOs\OrderTransferReportFilterDTO;

interface OrderTransferReportRepositoryInterface
{
    /**
     * جلب بيانات التقرير الخام بناءً على كائن الفلاتر
     *
     * @param OrderTransferReportFilterDTO $dto كائن الفلاتر المنقى
     * @return array المصفوفة الخام الناتجة عن الاستعلام
     */
    public function getRawReportData(OrderTransferReportFilterDTO $dto): array;
}