<?php

namespace App\Modules\Stock\Reports\OrderTransfersReports\Actions;

use App\Modules\Stock\Reports\OrderTransfersReports\DTOs\OrderTransferReportFilterDTO;
use App\Modules\Stock\Reports\OrderTransfersReports\Interfaces\OrderTransferReportRepositoryInterface;
use App\Modules\Stock\Reports\OrderTransfersReports\Resources\OrderTransferReportResource;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;

class FetchOrderTransferReportAction
{
    public function __construct(
        private readonly OrderTransferReportRepositoryInterface $repository
    ) {}

    /**
     * @return array ['paginator' => LengthAwarePaginator, 'grand_total' => string]
     */
    public function execute(OrderTransferReportFilterDTO $dto): array
    {
        // 1. جلب الإحصائيات (العدد والإجمالي المالي من SQL دفعة واحدة)
        $aggregates = $this->repository->getReportAggregates($dto);
        $totalRecords = $aggregates['total_records'];
        $grandTotal = $aggregates['grand_total'];

        if ($totalRecords === 0) {
            $paginator = new LengthAwarePaginator([], 0, $dto->perPage, $dto->page, [
                'path' => Paginator::resolveCurrentPath(),
            ]);

            return ['paginator' => $paginator, 'grand_total' => formatMoneyWithCurrency(0)];
        }

        // 2. جلب وتنسيق الصفحة الحالية فقط
        $rawReportData = $this->repository->getRawReportData($dto);
        $formattedItems = OrderTransferReportResource::transform($rawReportData);

        // 3. بناء الباجنيتور
        $paginator = new LengthAwarePaginator(
            $formattedItems,
            $totalRecords,
            $dto->perPage,
            $dto->page,
            ['path' => Paginator::resolveCurrentPath(), 'pageName' => 'page']
        );

        // 4. إرجاع النتائج جاهزة للـ View
        return [
            'paginator' => $paginator,
            'grand_total' => formatMoneyWithCurrency($grandTotal), // الإجمالي الكلي منسق وجاهز
        ];
    }
}
