<?php

namespace App\Exports;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;

class SalariesExport implements FromView
{
    /**
     * @return \Illuminate\Support\Collection
     */
    private $data = [];
    public function __construct($data)
    {
        $this->data = $data;
    }
    public function view(): View
    {
       $data = $this->data;
        return view(
            'export.reports.hr.salaries.salaries-excel',
            compact('data')
        );
    }
}
