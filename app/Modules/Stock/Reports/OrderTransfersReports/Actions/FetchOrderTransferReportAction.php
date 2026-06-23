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

    public function execute(OrderTransferReportFilterDTO $dto): array
    {
        // 1. جلب الإحصائيات (العدد والإجمالي المالي من SQL)
        $aggregates = $this->repository->getReportAggregates($dto);
       
        $totalRecords = $aggregates['total_records'];
        $grandTotal = $aggregates['grand_total'];

        if ($totalRecords === 0) {
            $paginator = new LengthAwarePaginator([], 0, $dto->perPage, $dto->page, [
                'path' => Paginator::resolveCurrentPath(),
            ]);

            return [
                'paginator' => $paginator,
                'current_page_total' => formatMoneyWithCurrency(0),
                'grand_total' => formatMoneyWithCurrency(0),
            ];
        }

        // 2. جلب وتنسيق الصفحة الحالية فقط
        $rawReportData = $this->repository->getRawReportData($dto);
        $formattedItems = OrderTransferReportResource::transform($rawReportData);
        $currentPageSubtotalRaw = collect($formattedItems)->sum(function($item) {
            return $item->subtotal_raw ?? 0;
        });
        $currentPagePriceTotalRaw = collect($rawReportData)->sum(function($item) {
            return $item->remaining_value ?? 0;
        });
        // dd($rawReportData,$formattedItems,$currentPagePriceTotalRaw);
        
        // ✅ حساب إجمالي الصفحة الحالية (سريع جداً لأنه يجمع 50 سجلاً فقط كحد أقصى)
        // $currentPageTotalRaw = collect($formattedItems)->sum('subtotal_raw');
$currentPageTotalRaw = collect($formattedItems)->sum(function($item) {
            return $item->subtotal_raw ?? 0;
        });
        // 3. بناء الباجنيتور
        $paginator = new LengthAwarePaginator(
            $formattedItems,
            $totalRecords,
            $dto->perPage,
            $dto->page,
            ['path' => Paginator::resolveCurrentPath(), 'pageName' => 'page']
        );

        // 4. إرجاع النتائج جاهزة
        return [
            'paginator' => $paginator,
            'current_page_price_total' => formatMoneyWithCurrency($currentPagePriceTotalRaw),
            'current_page_total' => formatMoneyWithCurrency($currentPageTotalRaw), // إجمالي الصفحة
            'grand_total' => formatMoneyWithCurrency($grandTotal),          // الإجمالي الكلي
        ];
    }
}
