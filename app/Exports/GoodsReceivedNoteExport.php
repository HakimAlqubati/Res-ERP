<?php

namespace App\Exports;

use App\Models\GoodsReceivedNote;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithTitle;

class GoodsReceivedNoteExport implements FromCollection, WithMapping, WithHeadings, ShouldAutoSize, WithTitle
{
    protected GoodsReceivedNote $grn;

    public function __construct(GoodsReceivedNote $grn)
    {
        $this->grn = $grn;
    }

    public function collection()
    {
        return $this->grn->grnDetails()->with(['product', 'unit'])->get();
    }

    public function map($detail): array
    {
        return [
            $this->grn->grn_number,
            $this->grn->grn_date,
            $this->grn->store?->name ?? '',
            $this->grn->supplier?->name ?? '',
            $detail->product?->code ?? '',
            $detail->product?->name ?? '',
            $detail->unit?->name ?? '',
            $detail->package_size ?? '',
            $detail->quantity ?? 0,
            $detail->price ?? 0,
            $detail->total_price ?? 0,
        ];
    }

    public function headings(): array
    {
        return [
            'GRN Number',
            'GRN Date',
            'Store',
            'Supplier',
            'Product Code',
            'Product Name',
            'Unit',
            'Package Size',
            'Quantity',
            'Price',
            'Total Price',
        ];
    }

    public function title(): string
    {
        return 'GRN ' . $this->grn->grn_number;
    }
}
