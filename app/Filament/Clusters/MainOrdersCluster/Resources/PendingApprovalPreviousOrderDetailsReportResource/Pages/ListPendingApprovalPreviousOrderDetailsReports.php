<?php

namespace App\Filament\Clusters\MainOrdersCluster\Resources\PendingApprovalPreviousOrderDetailsReportResource\Pages;

use App\Filament\Clusters\MainOrdersCluster\Resources\PendingApprovalPreviousOrderDetailsReportResource;
use App\Models\Order;
use App\Models\OrderDetails;
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
        $data = $this->generatePendingApprovalPreviousOrderDetailsReport($groupByOrder);
        
        return [
            'reportData' => $data,
        ];
    }


    public function generatePendingApprovalPreviousOrderDetailsReport($groupByOrder)
    { // ✅ افتراضي مفعّل التجميع

        // Fetch order details with the required conditions
        $orderDetails = OrderDetails::where('is_created_due_to_qty_preivous_order', 1)
            ->whereHas('order', function ($query) {
                $query->where('status', Order::PENDING_APPROVAL);
            })
            ->get();

        if ($groupByOrder) {
            // 🧩 إذا طلب تجميع
            $grouped = $orderDetails->groupBy('order_id');

            $result = [];
            foreach ($grouped as $orderId => $details) {
                $totalQuantity = $details->sum('quantity');

                $result[] = [
                    'order_id' => $orderId,
                    'total_quantity' => $totalQuantity,
                    'details' => $details->map(function ($detail) {
                        return [
                            'order_detail_id' => $detail->id,
                            'product_id' => $detail->product_id,
                            'product_code' => $detail->product->code,
                            'product_name' => $detail->product?->name,
                            'unit_id' => $detail->unit_id,
                            'unit_name' => $detail->unit?->name,
                            'quantity' => $detail->quantity,
                        ];
                    })->toArray(),
                ];
            }
            return $result;
        } else {
            // 🧩 إذا طلب عدم تجميع
            $result = $orderDetails->map(function ($detail) {
                return [
                    'order_detail_id' => $detail->id,
                    'order_id' => $detail->order_id,
                    'product_id' => $detail->product_id,
                    'product_code' => $detail->product->code,
                    'product_name' => $detail->product?->name,
                    'unit_id' => $detail->unit_id,
                    'unit_name' => $detail->unit?->name,
                    'quantity' => $detail->quantity,
                ];
            });
            return $result;
        }
    }
}
