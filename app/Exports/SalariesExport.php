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
    private $deducationTypes = [];
    private $allowanceTypes = [];
    public function __construct($data,$deducationTypes,$allowanceTypes)
    {
        $this->data = $data;
        $this->deducationTypes = $deducationTypes;
        $this->allowanceTypes = $allowanceTypes;
    }
    public function view(): View
    {
       $data = $this->data;
       $deducationTypes = $this->deducationTypes;
       $allowanceTypes = $this->allowanceTypes;
        return view(
            'export.reports.hr.salaries.salaries-excel',
            compact('data','deducationTypes','allowanceTypes')
        );
    }
}
