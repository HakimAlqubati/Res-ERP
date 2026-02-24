<?php

namespace App\Exports;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;

class PayrollsExport implements FromView
{
    private $payrolls;

    public function __construct($payrolls)
    {
        $this->payrolls = $payrolls;
    }

    public function view(): View
    {
        return view('export.reports.hr.payrolls.payrolls-excel', [
            'payrolls' => $this->payrolls,
        ]);
    }
}
