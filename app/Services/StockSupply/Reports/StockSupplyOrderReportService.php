<?php

namespace App\Services\StockSupply\Reports;

use App\Models\StockSupplyOrder;

class StockSupplyOrderReportService
{
    /**
     * Generate a report for StockSupplyOrder based on store_id and date range.
     *
     * @param int $storeId
     * @param string $startDate
     * @param string $endDate
     * @return array
     */
    public function generateReport(
        $storeId,
        $startDate,
        $endDate
    ): array {
        $orders = StockSupplyOrder::with(['store', 'details.product', 'details.unit'])
            ->where('store_id', $storeId)
            ->whereBetween('order_date', [$startDate, $endDate])
            ->get();

        return $orders->map(function ($order) {
            return [
                'order_id'     => $order->id,
                'order_date'   => $order->order_date,
                'store_name'   => $order->store->name ?? 'غير معروف',
                'notes'        => $order->notes,
                'item_count'   => $order->item_count,
                'details'      => $order->details->map(function ($detail) {
                    return [
                        'product_code' => $detail->product->code ?? null,
                        'product_name' => $detail->product->name ?? 'غير معروف',
                        'unit_name'    => $detail->unit->name ?? 'غير معروف',
                        'quantity'     => $detail->quantity,
                    ];
                })->toArray(),
            ];
        })->toArray();
    }
}
