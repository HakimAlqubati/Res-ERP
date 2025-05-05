<?php

namespace App\Services\Orders\Reports;

use App\Models\Order;
use Illuminate\Support\Facades\DB;

class OrdersReportsService
{
    /**
     * Get total requested quantities per product per branch over a period.
     *
     * @param string $fromDate
     * @param string $toDate
     * @param array|null $branchIds
     * @param array|null $productIds
     * @param array|null $categoryIds
     * @return array
     */
    public static function getBranchConsumption(
        string $fromDate,
        string $toDate,
        ?array $branchIds = null,
        ?array $productIds = null,
        ?array $categoryIds = null
    ): array {

        $query = Order::query()
            ->whereBetween('created_at', [$fromDate, $toDate])
            ->whereIn('status', [Order::DELEVIRED, Order::READY_FOR_DELEVIRY]);

        if ($branchIds && count($branchIds)) {
            $query->whereIn('branch_id', $branchIds);
        }

        if ($productIds && count($productIds)) {
            $query->whereHas('orderDetails', function ($q) use ($productIds) {
                $q->whereIn('product_id', $productIds);
            });
        }


        if ($categoryIds && count($categoryIds)) {
            $query->whereHas('orderDetails.product', function ($q) use ($categoryIds) {
                $q->whereIn('category_id', $categoryIds);
            });
        }
        $query->with([
            'orderDetails' => function ($q) use ($productIds) {
                if ($productIds && count($productIds)) {
                    $q->whereIn('product_id', $productIds);
                }
            },
            'orderDetails.product.category',
            'branch'
        ]);
        $results = $query->get()
            ->groupBy('branch_id')
            ->map(function ($orders, $branchId) {
                $branchName = $orders->first()->branch->name ?? 'Unknown Branch';

                $productData = [];

                foreach ($orders as $order) {
                    $orderDate = $order->created_at->format('Y-m-d');

                    foreach ($order->orderDetails as $detail) {
                        $product = $detail->product;
                        if (!$product) continue;

                        $productId = $product->id;
                        $productName = $product->name;
                        $categoryName = $product->category->name ?? 'Unknown';

                        if (!isset($productData[$productId])) {
                            $productData[$productId] = [
                                'product_id' => $productId,
                                'product_name' => $productName,
                                'category_name' => $categoryName,
                                'daily' => []
                            ];
                        }

                        if (!isset($productData[$productId]['daily'][$orderDate])) {
                            $productData[$productId]['daily'][$orderDate] = [
                                'date' => $orderDate,
                                'total_quantity' => 0,
                                'order_count' => 0,
                            ];
                        }

                        $productData[$productId]['daily'][$orderDate]['total_quantity'] += $detail->available_quantity;
                        $productData[$productId]['daily'][$orderDate]['order_count'] += 1;
                    }
                }

                // Convert 'daily' from assoc to indexed array
                foreach ($productData as &$product) {
                    $product['daily'] = array_values($product['daily']);
                }

                return [
                    'branch_id' => $branchId,
                    'branch_name' => $branchName,
                    'products' => array_values($productData),
                ];
            })
            ->values()
            ->toArray();

        return $results;
    }
}
