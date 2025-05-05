<?php

namespace App\Http\Controllers\Api\Reports;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\Orders\Reports\OrdersReportsService;

class BranchConsumptionController extends Controller
{
    public function index(Request $request)
    {
        $fromDate = $request->input('from_date', now()->subDays(7)->format('Y-m-d'));
        $toDate = $request->input('to_date', now()->format('Y-m-d'));
        $branchIds = $request->input('branch_ids');      // array
        $productIds = $request->input('product_ids');    // array
        $categoryIds = $request->input('category_ids');
        if($productIds && !is_array($productIds)){
            $productIds = explode(',', $productIds);
        }
        if($branchIds && !is_array($branchIds)){
            $branchIds = explode(',', $branchIds);
        }   
        if($categoryIds && !is_array($categoryIds)){
            $categoryIds = explode(',', $categoryIds);
        }
        
        $data = OrdersReportsService::getBranchConsumption(
            $fromDate,
            $toDate,
            $branchIds,
            $productIds,
            $categoryIds
        );

        return response()->json([
            'status' => 'success',
            'from_date' => $fromDate,
            'to_date' => $toDate,
            'data' => $data,
        ]);
    }
}
