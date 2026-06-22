<?php

namespace App\Modules\Stock\Reports\OrderTransfersReports\Actions;

use App\Modules\Stock\Reports\OrderTransfersReports\DTOs\OrderTransferReportFilterDTO;
use App\Modules\Stock\Reports\OrderTransfersReports\Interfaces\OrderTransferReportRepositoryInterface;
use App\Modules\Stock\Reports\OrderTransfersReports\Resources\OrderTransferReportResource;

class FetchOrderTransferReportAction
{
    /**
     * نحقن الـ Interface هنا بدلاً من الكلاس مباشرة (Dependency Injection)
     * هذا يرفع أداء النظام ويسهل عملية الاختبار (Testing).
     */
    public function __construct(
        private readonly OrderTransferReportRepositoryInterface $repository
    ) {}

    /**
     * تنفيذ المهمة
     *
     * @param OrderTransferReportFilterDTO $dto
     * @return array مصفوفة البيانات المنسقة والجاهزة للعرض
     */
    public function execute(OrderTransferReportFilterDTO $dto): array
    {
        // 1. جلب البيانات الخام من قاعدة البيانات بناءً على الفلاتر
        $rawReportData = $this->repository->getRawReportData($dto);

        // 2. إذا لم يكن هناك بيانات، نعيد مصفوفة فارغة فوراً لتوفير الموارد
        if (empty($rawReportData)) {
            return [];
        }

        // 3. إرسال البيانات الخام إلى كلاس التنسيق وإعادتها جاهزة
        return OrderTransferReportResource::transform($rawReportData);
    }
}