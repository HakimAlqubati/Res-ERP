<?php

namespace App\Exports;

use App\Models\Order;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;

class OrdersExport2 implements FromArray, WithHeadings
{
    protected $orders;

    public function __construct(Collection $orders)
    {
        $this->orders = $orders;
    }

    public function array(): array
    {
        $data = [];
    
        foreach ($this->orders->load(['customer', 'branch', 'orderDetails.product', 'orderDetails.unit']) as $order) {
            // ✅ Header row
            $data[] = [
                'row_type' => 'header',
                'order_id' => $order->id,
                'branch_id' => $order->branch_id,
                'customer_id' => $order->customer_id,
                'status' => $order->status,
                'notes' => $order->notes,
                'created_at' => $order->created_at,
                'product_id' => '',
                'unit_id' => '',
                'quantity' => '',
                'price' => '',
                'available_quantity' => '',
            ];
    
            // ✅ Detail rows
            foreach ($order->orderDetails as $detail) {
                $data[] = [
                    'row_type' => 'detail',
                    'order_id' => $order->id,
                    'branch_id' => '',
                    'customer_id' => '',
                    'status' => '',
                    'notes' => '',
                    'created_at' => '',
                    'product_id' => $detail->product_id,
                    'unit_id' => $detail->unit_id,
                    'quantity' => $detail->quantity,
                    'price' => $detail->price,
                    'available_quantity' => $detail->available_quantity,
                ];
            }
        }
    
        return $data;
    }
    
    public function headings(): array
    {
        return [
            'row_type',
            'order_id',
            'branch_id',
            'customer_id',
            'status',
            'notes',
            'created_at',
            'product_id',
            'unit_id',
            'quantity',
            'price',
            'available_quantity',
        ];
    }
    
}
