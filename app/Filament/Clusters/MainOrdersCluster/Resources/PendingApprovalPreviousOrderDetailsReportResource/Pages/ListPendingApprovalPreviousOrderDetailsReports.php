<?php

namespace App\Filament\Clusters\MainOrdersCluster\Resources\PendingApprovalPreviousOrderDetailsReportResource\Pages;

use App\Filament\Clusters\MainOrdersCluster\Resources\PendingApprovalPreviousOrderDetailsReportResource;
use App\Models\Order;
use App\Models\OrderDetails;
use App\Services\Orders\Reports\ReorderDueToStockReportService;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Model;

class ListPendingApprovalPreviousOrderDetailsReports extends ListRecords
{
    protected static string $resource = PendingApprovalPreviousOrderDetailsReportResource::class;
    protected static string $view = 'filament.pages.order-reports.pending-approval-previous-order-details-report';

    public function getTableRecordKey(Model $record): string
    {
        $attributes = $record->getAttributes();
        return $attributes['product_id'];
    }
    protected function getViewData(): array
    {
        $groupByOrder = $this->getTable()->getFilters()['show_extra_fields']->getState()['group_by_order'] ?? 0;

        $data =  (new ReorderDueToStockReportService())->getReorderDueToStockReport($groupByOrder);
        
        return [
            'reportData' => $data,
            'groupByOrder' => $groupByOrder,
        ];
    }
}
