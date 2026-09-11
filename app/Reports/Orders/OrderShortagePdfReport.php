<?php

namespace App\Reports\Orders;

use App\Models\Order;
use App\Models\OrderDetails;
use App\Services\MultiProductsInventoryService;
use Illuminate\Support\Collection;
use Mccarlosen\LaravelMpdf\Facades\LaravelMpdf;

class OrderShortagePdfReport
{
    protected array $inventoryCache = [];

    /**
     * Gather shortage report data.
     *
     * @param int|null $orderId If provided, report is restricted to this order.
     * @param array $options Additional filters: 'order_ids', 'branch_id', 'status', etc.
     * @return array
     */
    public function getData(?int $orderId = null, array $options = []): array
    {
        $query = OrderDetails::query()
            ->with([
                'order.branch',
                'product.category',
                'unit',
            ]);

        if ($orderId) {
            $query->where('order_id', $orderId);
        } elseif (!empty($options['order_ids'])) {
            $query->whereIn('order_id', (array) $options['order_ids']);
        } else {
            // Filter orders between 9662 AND 9687 by default
            $fromOrder = $options['from_order'] ?? 9662;
            $toOrder   = $options['to_order'] ?? 9687;
            $query->whereBetween('order_id', [$fromOrder, $toOrder]);

            if (!empty($options['branch_id']) || !empty($options['status'])) {
                $query->whereHas('order', function ($q) use ($options) {
                    if (!empty($options['branch_id'])) {
                        $q->where('branch_id', $options['branch_id']);
                    }
                    if (!empty($options['status'])) {
                        $q->where('status', $options['status']);
                    }
                });
            }
        }

        $orderDetails = $query->orderBy('order_id', 'asc')->get();

        $items = [];
        $totalAvailable = 0;
        $totalRemaining = 0;
        $totalShortage = 0;
        $affectedOrderIds = [];

        foreach ($orderDetails as $detail) {
            $product = $detail->product;
            if (!$product) {
                continue;
            }

            $store = defaultManufacturingStore($product);
            $storeId = $store?->id ?? null;
            $storeName = $store?->name ?? __('lang.unspecified', [], 'ar') ?: 'غير محدد';

            $remainingQty = $this->getRemainingStock($detail->product_id, $detail->unit_id, $storeId);
            $availableQty = (float) $detail->available_quantity;

            if ($availableQty > $remainingQty) {
                $shortage = round($availableQty - $remainingQty, 4);

                $items[] = [
                    'order_id'          => $detail->order_id,
                    'order_date'        => $detail->order?->order_date ?? $detail->order?->created_at?->format('Y-m-d') ?? '-',
                    'branch_name'       => $detail->order?->branch?->name ?? '-',
                    'store_name'        => $storeName,
                    'store_id'          => $storeId,
                    'product_code'      => $product->code ?? '-',
                    'product_name'      => $product->name ?? '-',
                    'unit_name'         => $detail->unit?->name ?? '-',
                    'ordered_quantity'  => (float) $detail->quantity,
                    'available_quantity'=> $availableQty,
                    'remaining_quantity'=> $remainingQty,
                    'shortage_quantity' => $shortage,
                ];

                $totalAvailable += $availableQty;
                $totalRemaining += $remainingQty;
                $totalShortage += $shortage;
                $affectedOrderIds[$detail->order_id] = true;
            }
        }

        $targetOrder = $orderId ? Order::with('branch')->find($orderId) : null;

        return [
            'items'               => $items,
            'is_single_order'     => !is_null($orderId),
            'target_order'        => $targetOrder,
            'from_order'          => $fromOrder ?? ($options['from_order'] ?? null),
            'to_order'            => $toOrder ?? ($options['to_order'] ?? null),
            'total_items_count'   => count($items),
            'total_orders_count'  => count($affectedOrderIds),
            'total_available_qty' => $totalAvailable,
            'total_remaining_qty' => $totalRemaining,
            'total_shortage_qty'  => $totalShortage,
            'generated_at'        => now()->format('Y-m-d H:i:s'),
        ];
    }

    /**
     * Retrieve remaining stock using MultiProductsInventoryService with in-memory memoization.
     */
    protected function getRemainingStock(?int $productId, $unitId, ?int $storeId): float
    {
        if (!$productId || !$storeId) {
            return 0.0;
        }

        $cacheKey = "{$productId}_{$unitId}_{$storeId}";

        if (!array_key_exists($cacheKey, $this->inventoryCache)) {
            $service = new MultiProductsInventoryService(
                null,
                $productId,
                $unitId,
                $storeId
            );

            $inv = $service->getInventoryForProduct($productId);
            $qty = $inv[0]['remaining_qty'] ?? 0;
            $this->inventoryCache[$cacheKey] = (float) $qty;
        }

        return $this->inventoryCache[$cacheKey];
    }

    /**
     * Build the mPDF instance.
     *
     * @param int|null $orderId
     * @param array $options
     * @return mixed
     */
    public function makePdf(?int $orderId = null, array $options = [])
    {
        $data = $this->getData($orderId, $options);

        $pdfConfig = [
            'format'        => 'A4-L', // Landscape for wide table with all columns
            'orientation'   => 'L',
            'margin_left'   => 8,
            'margin_right'  => 8,
            'margin_top'    => 10,
            'margin_bottom' => 10,
        ];

        return LaravelMpdf::loadView('reports.orders.order_shortage_pdf', $data, [], $pdfConfig);
    }

    /**
     * Return a stream download response.
     *
     * @param int|null $orderId
     * @param array $options
     * @return \Symfony\Component\HttpFoundation\StreamedResponse
     */
    public function download(?int $orderId = null, array $options = [])
    {
        $pdf = $this->makePdf($orderId, $options);

        $filename = $orderId
            ? "order_{$orderId}_shortage_report_" . date('Ymd_His') . '.pdf'
            : "orders_shortage_report_" . date('Ymd_His') . '.pdf';

        return response()->streamDownload(function () use ($pdf) {
            echo $pdf->output();
        }, $filename);
    }

    /**
     * Save PDF directly to disk.
     *
     * @param string $destinationPath Full file path
     * @param int|null $orderId
     * @param array $options
     * @return string Path where file was saved
     */
    public function save(string $destinationPath, ?int $orderId = null, array $options = []): string
    {
        $pdf = $this->makePdf($orderId, $options);

        $dir = dirname($destinationPath);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $pdf->save($destinationPath);

        return $destinationPath;
    }
}
