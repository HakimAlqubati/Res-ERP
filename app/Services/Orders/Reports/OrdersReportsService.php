<?php

namespace App\Services\Orders\Reports;

use App\Models\Order;

class OrdersReportsService
{
    public const INTERVAL_DAILY = 'daily';
    public const INTERVAL_WEEKLY = 'weekly';
    public const INTERVAL_MONTHLY = 'monthly';

    public const INTERVALS = [
        self::INTERVAL_DAILY,
        self::INTERVAL_WEEKLY,
        self::INTERVAL_MONTHLY,
    ];

    /**
     * Get total requested quantities per product per branch over a period.
     *
     * @param string $fromDate
     * @param string $toDate
     * @param array|null $branchIds
     * @param array|null $productIds
     * @param array|null $categoryIds
     * @param string $intervalType
     * @return array
     */
    public static function getBranchConsumption(
        string $fromDate,
        string $toDate,
        ?array $branchIds = null,
        ?array $productIds = null,
        ?array $categoryIds = null,
        string $intervalType = self::INTERVAL_DAILY
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
            'branch',
        ]);

        $results = $query->get()
            ->groupBy('branch_id')
            ->map(function ($orders, $branchId) use ($intervalType) {
                $branchName = $orders->first()->branch->name ?? 'Unknown Branch';

                $productData = [];

                foreach ($orders as $order) {
                    $orderDate = match ($intervalType) {
                        self::INTERVAL_WEEKLY => $order->created_at->startOfWeek()->format('Y-m-d'),
                        self::INTERVAL_MONTHLY => $order->created_at->startOfMonth()->format('Y-m'),
                        default => $order->created_at->format('Y-m-d'),
                    };

                    foreach ($order->orderDetails as $detail) {
                        $product = $detail->product;

                        if (!$product) continue;

                        $productId = $product->id;
                        $productName = $product->name;
                        $categoryName = $product->category->name ?? 'Unknown';
                        $unitId = $detail->unit_id;

                        if (!isset($productData[$productId])) {
                            $productData[$productId] = [
                                'product_id' => $productId,
                                'product_name' => $productName,
                                'category_name' => $categoryName,
                                'unit_id' => $unitId,
                                'unit_name' => $detail->unit->name ?? '',
                                'daily' => [],
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

                // تحويل البيانات اليومية إلى مصفوفة رقمية
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


    public static function getTopBranches(array $reportData, int $limit = null): array
    {
        $branches = collect($reportData)
            ->map(function ($branch) {
                $total = collect($branch['products'])->flatMap(fn($p) => $p['daily'])->sum('total_quantity');
                return [
                    'branch_id' => $branch['branch_id'],
                    'branch_name' => $branch['branch_name'],
                    'total_quantity' => $total,
                ];
            })
            ->sortByDesc('total_quantity')
            ->values();

        return $limit ? $branches->take($limit)->toArray() : $branches->toArray();
    }

    public static function getTopProducts(array $reportData, int $limit = 10): array
    {
        $products = collect($reportData)
            ->flatMap(fn($branch) => $branch['products'])
            ->groupBy('product_id')
            ->map(function ($group) {
                $first = $group->first();
                $total = $group->flatMap(fn($p) => $p['daily'])->sum('total_quantity');
                return [
                    'product_id' => $first['product_id'],
                    'product_name' => $first['product_name'],
                    'category_name' => $first['category_name'],
                    'unit_name' => $first['unit_name'],
                    'total_quantity' => $total,
                ];
            })
            ->sortByDesc('total_quantity')
            ->values()
            ->take($limit)
            ->toArray();

        return $products;
    }

    public static function getTopProductsBasedBranches(array $report, int $limit = 10): array
    {
        $productMap = [];

        foreach ($report as $branch) {
            $branchId = $branch['branch_id'];
            $branchName = $branch['branch_name'];

            foreach ($branch['products'] as $product) {
                $productId = $product['product_id'];
                $productName = $product['product_name'];
                $unitName = $product['unit_name'];

                if (!isset($productMap[$productId])) {
                    $productMap[$productId] = [
                        'product_id' => $productId,
                        'product_name' => $productName,
                        'unit_name' => $unitName,
                        'unit_id' => $product['unit_id'],
                        'total_quantity' => 0,
                        'branches' => [],
                    ];
                }

                $branchTotal = 0;
                $branchOrderCount = 0;

                foreach ($product['daily'] as $entry) {
                    $branchTotal += $entry['total_quantity'];
                    $branchOrderCount += $entry['order_count'];

                }

                $productMap[$productId]['total_quantity'] += $branchTotal;

                $productMap[$productId]['branches'][] = [
                    'branch_id' => $branchId,
                    'branch_name' => $branchName,
                    'quantity' => $branchTotal,
                    'order_count' => $branchOrderCount,

                ];
            }
        }

        // ترتيب المنتجات حسب إجمالي الكمية نزولًا
        usort($productMap, fn($a, $b) => $b['total_quantity'] <=> $a['total_quantity']);

        return array_slice(array_values($productMap), 0, $limit);
    }
}
