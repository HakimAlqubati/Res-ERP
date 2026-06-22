<?php

namespace App\Filament\Resources\OrderReportsResource\Pages;

use App\Filament\Resources\OrderReportsResource\ReportProductQuantitiesResource;
use App\Models\Branch;
use App\Modules\Stock\Reports\OrderTransfersReports\Actions\FetchOrderTransferReportAction;
use App\Modules\Stock\Reports\OrderTransfersReports\DTOs\OrderTransferReportFilterDTO;
use Filament\Actions\Action;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Model;

class ListReportProductQuantities extends ListRecords
{
    protected static string $resource = ReportProductQuantitiesResource::class;

    protected string $view = 'filament.pages.order-reports.report-product-quantities';

    public function getTableRecordKey(Model|array $record): string
    {
        $attributes = $record->getAttributes();

        return $attributes['product'].'-'.$attributes['branch'].'-'.$attributes['unit'];
    }

    protected function getViewData(): array
    {

        $filters = [
            'branch_id' => $this->getTable()->getFilters()['branch_id']->getState()['values'] ?? [],
            'start_date' => $this->getTable()->getFilters()['date']->getState()['start_date'] ?? null,
            'end_date' => $this->getTable()->getFilters()['date']->getState()['end_date'] ?? null,
            'product_id' => $this->getTable()->getFilters()['product_id']->getState()['value'] ?? null,
            'category_id' => $this->getTable()->getFilters()['category_id']->getState()['values'] ?? [],
        ];

        // إذا لم يتم تحديد فروع، يتم اختيار الفروع الافتراضية
        if (empty($filters['branch_id'])) {
            $filters['branch_id'] = Branch::whereIn('type', [
                Branch::TYPE_BRANCH,
                Branch::TYPE_CENTRAL_KITCHEN,
                Branch::TYPE_POPUP,
            ])->activePopups()->active()->pluck('id')->toArray();
        }
        // 2. إنشاء كائن الـ DTO لضمان نظافة البيانات
        $filterDTO = OrderTransferReportFilterDTO::fromArray($filters);

        // 3. استدعاء الأكشن (Action) الذي سيقوم بكل العمل باستخدام الحقن (Dependency Injection)
        $action = app(FetchOrderTransferReportAction::class);

        // 4. تنفيذ الأكشن والحصول على البيانات المنسقة مباشرة
        $reportData = $action->execute($filterDTO);

        // $totalPrice = $data->sum('price');
        return [
            'report_data' => $reportData,
            'product_id' => $filterDTO->productId,
            'start_date' => $filters['start_date'],
            'end_date' => $filters['end_date'], ];

        return [];
    }
}
