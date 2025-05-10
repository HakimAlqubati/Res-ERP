<?php

namespace App\Http\Controllers\Landing;

use App\Http\Controllers\Controller;

class WorkbenchController extends Controller
{
    public function show()
    {
        return view('landing.workbench');
    }
    public function restaurantErp()
    {
        return view('landing.restaurant-erp');
    }

    public function faq()
    {
        return view('landing.faq');
    }
}
