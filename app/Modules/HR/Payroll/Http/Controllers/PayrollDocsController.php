<?php

namespace App\Modules\HR\Payroll\Http\Controllers;

use App\Http\Controllers\Controller;

class PayrollDocsController extends Controller
{
    /**
     * Show the payroll logic flow diagram.
     */
    public function logicFlow()
    {
        return view('payroll::documentation.logic-flow');
    }
}
