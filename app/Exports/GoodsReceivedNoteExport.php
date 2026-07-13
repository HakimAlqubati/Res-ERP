<?php

namespace App\Exports;

use App\Models\GoodsReceivedNote;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithTitle;

class GoodsReceivedNoteExport implements FromView, ShouldAutoSize, WithTitle
{
    protected GoodsReceivedNote $grn;

    public function __construct(GoodsReceivedNote $grn)
    {
        $this->grn = $grn;
    }

    public function view(): View
    {
        return view('export.goods_received_note', [
            'grn' => $this->grn
        ]);
    }

    public function title(): string
    {
        return 'GRN ' . $this->grn->grn_number;
    }
}
