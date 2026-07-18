<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use App\Models\StockInventory;

class StockInventoryDetailsExport implements FromArray, WithHeadings, ShouldAutoSize
{
    private StockInventory $inventory;

    public function __construct(StockInventory $inventory)
    {
        $this->inventory = $inventory;
    }

    public function array(): array
    {
        $data = [];
        $totalClosingValue = 0;

        foreach ($this->inventory->details as $detail) {
            $unitPrice = getUnitPrice($detail->product_id, $detail->unit_id) ?? 0;
            $physicalQty = $detail->physical_quantity ?? 0;
            $totalPrice = $physicalQty * $unitPrice;
            
            $totalClosingValue += $totalPrice;

            $data[] = [
                $detail->product ? "{$detail->product->code}-{$detail->product->name}" : 'N/A',
                $detail->unit ? $detail->unit->name : 'N/A',
                $detail->package_size,
                $detail->system_quantity,
                $detail->physical_quantity,
                $totalPrice,
                $detail->difference,
                $detail->is_adjustmented ? 'Yes' : 'No',
            ];
        }

        // Add Total Row
        $data[] = [
            '', // Product
            '', // Unit
            '', // Package Size
            '', // System Qty
            'Total:', // Physical Qty
            $totalClosingValue, // Total Price
            '', // Difference
            '', // Adjusted
        ];

        return $data;
    }

    public function headings(): array
    {
        return [
            'Product',
            'Unit',
            'Package Size',
            'System Qty',
            'Physical Qty',
            'Total Price',
            'Difference',
            'Is Adjusted',
        ];
    }
}
