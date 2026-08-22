<?php

namespace App\Exports;

use App\Contracts\InventoryPriceResolver;
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
        $this->inventory->loadMissing('details.product', 'details.unit');

        // ── Resolve all prices in one batch (same strategy as UI) ──
        $resolver = app(InventoryPriceResolver::class);
        $prices   = $resolver->resolveForInventory($this->inventory);

        $data = [];
        $totalClosingValue = 0;

        foreach ($this->inventory->details as $detail) {
            $key       = $detail->product_id . '_' . $detail->unit_id;
            $priceData = $prices->get($key);
            $unitPrice = $priceData ? (float) $priceData->unit_price : 0;

            $physicalQty = $detail->physical_quantity ?? 0;
            $totalPrice  = $physicalQty * $unitPrice;

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

