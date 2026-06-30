<?php

namespace App\Exports;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;

class InventoryTransactionReportExport implements FromView
{
    private $data = [];

    public function __construct($data)
    {
        $this->data = $data;
    }

    public function view(): View
    {
        $reportData = $this->data; 
        return view(
            'export.reports.inventory.inventory-transaction-report-export',
            compact('reportData')
        );
    }
}
