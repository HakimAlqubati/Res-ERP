<?php

namespace App\Exports;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;

class InventoryTransactionReportExport implements FromView
{
    private $data = [];
    private $storeName;
    private $date;

    public function __construct($data, $storeName = 'All Stores', $date = null)
    {
        $this->data = $data;
        $this->storeName = $storeName;
        $this->date = $date ?? now()->format('Y-m-d');
    }

    public function view(): View
    {
        $reportData = $this->data; 
        $storeName = $this->storeName;
        $date = $this->date;
        return view(
            'export.reports.inventory.inventory-transaction-report-export',
            compact('reportData', 'storeName', 'date')
        );
    }
}
