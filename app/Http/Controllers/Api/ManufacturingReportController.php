<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\Inventory\ManufacturingAllocationReportService;

class ManufacturingReportController extends Controller
{
    protected $service;
 
    public function index()
    {
       
        return [];
    }
}
