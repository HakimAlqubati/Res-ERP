<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Illuminate\Support\Collection;

class StockInventoriesExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize
{
    private Collection $records;

    public function __construct(Collection $records)
    {
        $this->records = $records;
    }

    public function collection()
    {
        return $this->records;
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

    public function map($record): array
    {
        return [
            $record->id,
            $record->inventory_date,
            $record->categories_names,
            $record->details_count,
            $record->store ? $record->store->name : '',
            $record->closing_stock_value,
            $record->responsibleUser ? $record->responsibleUser->name : '',
            $record->finalized ? 'Yes' : 'No',
            $record->finalized ? (\App\Models\StockAdjustmentDetail::where('source_id', $record->id)
                ->where('source_type', get_class($record))
                ->latest('adjustment_date')
                ->value('adjustment_date') ?? $record->updated_at?->format('Y-m-d')) : '-',
        ];
    }
}
