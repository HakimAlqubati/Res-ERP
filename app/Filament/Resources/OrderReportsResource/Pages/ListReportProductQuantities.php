<?php

namespace App\Filament\Resources\OrderReportsResource\Pages;

use App\Filament\Resources\OrderReportsResource\ReportProductQuantitiesResource;
// ← مهم جداً لسلاسة الأياكس
use App\Models\Branch;
use App\Modules\Stock\Reports\OrderTransfersReports\Actions\FetchOrderTransferReportAction;
use App\Modules\Stock\Reports\OrderTransfersReports\DTOs\OrderTransferReportFilterDTO;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Pagination\Paginator;

class ListReportProductQuantities extends ListRecords
{
    protected static string $resource = ReportProductQuantitiesResource::class;

    protected string $view = 'filament.pages.order-reports.report-product-quantities';

    // نحدد ثيم التصفح ليكون متوافق مع Filament (اختياري لكن يفضل)
    protected string $paginationTheme = 'tailwind';

    protected function getViewData(): array
    {
        // استخراج رقم الصفحة الحالية الذي يرسله Livewire تلقائياً
        $currentPage = Paginator::resolveCurrentPage('page');
        $perPage = 50; // غير هذا الرقم كما تحب لعدد السجلات في كل صفحة

        $filters = [
            'branch_id' => $this->getTable()->getFilters()['branch_id']->getState()['values'] ?? [],
            'start_date' => $this->getTable()->getFilters()['date']->getState()['start_date'] ?? null,
            'end_date' => $this->getTable()->getFilters()['date']->getState()['end_date'] ?? null,
            'product_id' => $this->getTable()->getFilters()['product_id']->getState()['value'] ?? null,
            'category_id' => $this->getTable()->getFilters()['category_id']->getState()['values'] ?? [],
        ];

        if (empty($filters['branch_id'])) {
            $filters['branch_id'] = Branch::whereIn('type', [
                Branch::TYPE_BRANCH, Branch::TYPE_CENTRAL_KITCHEN, Branch::TYPE_POPUP,
            ])->activePopups()->active()->pluck('id')->toArray();
        }

        // إنشاء الـ DTO
        $filterDTO = OrderTransferReportFilterDTO::fromArray($filters, $currentPage, $perPage);

        // استدعاء الأكشن للحصول على كائن LengthAwarePaginator
        $action = app(FetchOrderTransferReportAction::class);
         $result = $action->execute($filterDTO);

        return [
            'report_data' => $result['paginator'],    // مصفوفة السجلات والتصفح
            'grand_total' => $result['grand_total'],  // الإجمالي الكلي الجاهز
          'current_page_total' => $result['current_page_total'], // ✅ إضافة إجمالي الصفحة الحالية
         'current_page_price_total' => $result['current_page_price_total'], // ✅ المتغير الجديد
          'product_id' => $filterDTO->productId,
            'start_date' => $filters['start_date'],
            'end_date' => $filters['end_date'],
        ];
    }
}
