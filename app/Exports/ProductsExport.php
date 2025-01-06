<?php

namespace App\Exports;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;

class ProductsExport implements FromView
{
    /**
     * @return \Illuminate\Support\Collection
     */
    private $data = [];
    private $deducationTypes = [];
    private $allowanceTypes = [];
    public function __construct($data)
    {
        $this->data = $data;
    }
    public function view(): View
    {
       $data = $this->data; 
        return view(
            'export.products',
            compact('data')
        );
    }
}
