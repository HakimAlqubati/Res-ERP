<?php

namespace App\Exports;

use App\Modules\Stock\Reports\StockInventoryValuationReport\DTOs\StockInventoryValuationReportDTO;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;

class StocktakeValuationReportExport implements FromArray, WithHeadings, ShouldAutoSize
{
    public function __construct(
        private readonly StockInventoryValuationReportDTO $report
    ) {}

    public function array(): array
    {
        $rows = [];

        foreach ($this->report->items as $item) {
            $rows[] = [
                $item->productCode,
                $item->productName,
                $item->unitName,
                $item->packageSize,
                $item->physicalQty,
                $item->unitPrice,
                $item->totalValue,
            ];
        }

        // Grand Total Row
        $rows[] = [
            '',
            '',
            '',
            '',
            '',
            'Grand Total Price:',
            $this->report->grandTotalValue,
        ];

        return $rows;
    }

    public function headings(): array
    {
        return [
            'Product Code',
            'Product Name',
            'Unit',
            'Qty Per Pack',
            'Physical Qty',
            'Unit Price',
            'Total Price',
        ];
    }
}
