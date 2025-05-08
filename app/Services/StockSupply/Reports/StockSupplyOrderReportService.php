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
        $orders = StockSupplyOrder::with(['details.product', 'details.unit'])
            ->where('store_id', $storeId)
            ->whereBetween('order_date', [$startDate, $endDate])
            ->get();

        $aggregated = [];

        foreach ($orders as $order) {
            foreach ($order->details as $detail) {
                $key = $detail->product_id . '-' . $detail->unit_id;

                if (!isset($aggregated[$key])) {
                    $aggregated[$key] = [
                        'product_name'   => $detail->product->name ?? 'Unknown',
                        'product_code'   => $detail->product->code ?? null,
                        'unit_name'      => $detail->unit->name ?? 'Unknown',
                        'total_quantity' => 0,
                    ];
                }

                $aggregated[$key]['total_quantity'] += $detail->quantity;
            }
        }

        return array_values($aggregated); // re-index the array for frontend use
    }
}
