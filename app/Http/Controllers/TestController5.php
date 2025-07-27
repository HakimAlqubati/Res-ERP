<?php
namespace App\Http\Controllers;

use App\Services\PurchasedReports\PurchaseInvoiceProductSummaryReportService;
use Illuminate\Http\Request;

class TestController5 extends Controller
{
    public function stockCostReport(Request $request)
    {
        $filters = $request->only([
            'product_id',
            'unit_id',
            'purchase_invoice_id',
            'date_from',
            'date_to',
            'details',
            'store_id',
        ]);

        $groupByInvoice = $request->boolean('group_by_invoice', false);
        $groupByPrice   = $request->boolean('group_by_price', false);

        $reportService = new PurchaseInvoiceProductSummaryReportService();
        $purchasedData = $reportService->getProductSummaryPerInvoice($filters);
        return [
            'count' => count($purchasedData),
            'data'  => $purchasedData,
        ];
    }

    public function orderdData(Request $request)
    {
        $filters = $request->only([
            'product_id',
            'store_id',
            'details',
        ]);

        $reportService = new PurchaseInvoiceProductSummaryReportService();
        $orderdData    = $reportService->getOrderedProductsLinkedToPurchase($filters);
        return [
            'count' => count($orderdData),
            'data'  => $orderdData,
        ];
    }

    public function purchasedVSordered(Request $request)
    {
        $filters = $request->only([
            'product_id',
            'group_by_invoice',
        ]);

        $reportService = new PurchaseInvoiceProductSummaryReportService();

        $purchased = $reportService->getProductSummaryPerInvoice($filters); // or with filters
        $ordered   = $reportService->getOrderedProductsLinkedToPurchase($filters);

        $diffReport = $reportService->calculatePurchaseVsOrderedDifference($purchased, $ordered);
        // $productIds = Arr::pluck($diffReport, 'product_id');

        // $productIdsString = implode(',', $productIds);
        // $pids = [109, 171, 266, 22, 100, 21, 178, 192, 316, 169, 246, 388, 12, 166, 110, 104, 267, 156, 23, 13, 4, 36, 107, 317, 170, 207, 33, 111, 92, 24, 5, 158, 20, 167, 118, 117, 315, 245, 385, 164, 102, 1071, 318, 121, 264, 94, 165, 88, 258,143,139];
        // dd($productIds, $pids);
        return [
            'count' => count($diffReport),
            'data'  => $diffReport,
        ];
    }
}