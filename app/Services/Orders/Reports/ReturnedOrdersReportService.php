<?php

namespace App\Services\Orders\Reports;

use App\Models\ReturnedOrder;
use Illuminate\Support\Collection;

class ReturnedOrdersReportService
{
    /**
     * Generate a report for returned orders with item details.
     *
     * @param array $filters [
     *      'branch_id' => int|null,
     *      'store_id' => int|null,
     *      'status' => string|null,
     *      'start_date' => string|null,
     *      'end_date' => string|null,
     * ]
     * @return Collection
     */
    public function generate(array $filters = []): Collection
    {
        $query = ReturnedOrder::with(['details.product', 'details.unit', 'branch', 'creator', 'approver', 'store']);

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        } else {
            $query->approved(); // default to approved
        }

        if (!empty($filters['branch_id'])) {
            $query->where('branch_id', $filters['branch_id']);
        }

        if (!empty($filters['store_id'])) {
            $query->where('store_id', $filters['store_id']);
        }

        if (!empty($filters['start_date'])) {
            $query->whereDate('returned_date', '>=', $filters['start_date']);
        }

        if (!empty($filters['end_date'])) {
            $query->whereDate('returned_date', '<=', $filters['end_date']);
        }

        return $query->get()->map(function ($order) {
            return [
                'id'             => $order->id,
                'date'           => $order->returned_date->format('Y-m-d'),
                'branch'         => $order->branch->name ?? '',
                'store'          => $order->store->name ?? '',
                'created_by'     => $order->creator->name ?? '',
                'approved_by'    => $order->approver->name ?? '',
                'status'         => $order->status,
                'items_count'    => $order->items_count,
                'total_quantity' => $order->total_quantity,
                'total_amount'   => $order->total_amount,
                'details'        => $order->details->map(function ($detail) {
                    return [
                        'product'  => $detail->product->name ?? '',
                        'unit'     => $detail->unit->name ?? '',
                        'quantity' => $detail->quantity,
                        'price'    => $detail->price,
                        'total'    => $detail->total_price,
                        'notes'    => $detail->notes,
                    ];
                })->toArray(),
            ];
        });
    }
}
