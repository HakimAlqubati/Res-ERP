<?php

namespace App\Http\Controllers\Landing;

use App\Http\Controllers\Controller;

class WorkbenchController extends Controller
{
    public function show()
    {
        return view('landing.workbench');
    }
}
