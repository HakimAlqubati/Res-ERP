<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Illuminate\Support\Collection;

class StockInventoriesExport implements FromArray, WithHeadings, ShouldAutoSize
{
    private Collection $records;

    public function __construct(Collection $records)
    {
        $this->records = $records;
    }

    public function array(): array
    {
        $data = [];
        $totalClosingValue = 0;

        foreach ($this->records as $record) {
            $closingValue = (float) ($record->closing_stock_value ?? 0);
            $totalClosingValue += $closingValue;

            $data[] = [
                $record->id,
                $record->inventory_date,
                $record->categories_names,
                $record->details_count,
                $record->store ? $record->store->name : '',
                $closingValue,
                $record->responsibleUser ? $record->responsibleUser->name : '',
                $record->finalized ? 'Yes' : 'No',
                $record->finalized ? (\App\Models\StockAdjustmentDetail::where('source_id', $record->id)
                    ->where('source_type', get_class($record))
                    ->latest('adjustment_date')
                    ->value('adjustment_date') ?? $record->updated_at?->format('Y-m-d')) : '-',
            ];
        }

        // Add Total Row
        $data[] = [
            '', // ID
            '', // Date
            '', // Categories
            '', // Products No
            'Total ', // Store 
            $totalClosingValue, // Closing Stock Value
            '', // Responsible
            '', // Finalized
            '', // Finalized Date
        ];

        return $data;
    }

    public function headings(): array
    {
        return [
            'ID',
            'Date',
            'Categories',
            'Products No',
            'Store',
            'Closing Stock Value',
            'Responsible',
            'Finalized',
            'Finalized Date',
        ];
    }
}
