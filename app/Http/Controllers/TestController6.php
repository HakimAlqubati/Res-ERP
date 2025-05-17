<?php

namespace App\Http\Controllers;

use App\Services\Reports\CenteralKitchens\InVsOutReportService;
use Illuminate\Http\Request;

class TestController6 extends Controller
{
    public function getInData(Request $request){

        $filters = $request->only([
            'product_id', 
            'store_id',
            'to_date',
        ]);

        $reportService = new InVsOutReportService();

        $data = $reportService->getInData($filters);
        return response()->json($data);
    }
    public function getOutData(Request $request){

        $filters = $request->only([
            'product_id', 
            'store_id',
            'to_date',
        ]);

        $reportService = new InVsOutReportService();

        $data = $reportService->getOutData($filters);
        return response()->json($data);
    }

    public function getFinalComparison(Request $request){

        $filters = $request->only([
            'product_id', 
            'store_id',
            'to_date',
        ]);

        $reportService = new InVsOutReportService();

        $data = $reportService->getFinalComparison($filters);
        return response()->json($data);
    }
}
