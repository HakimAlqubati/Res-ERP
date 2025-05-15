<?php

namespace App\Http\Controllers;

use App\Models\InventoryTransaction;
use App\Services\PurchasedReports\PurchaseInvoiceProductSummaryReportService;
use Illuminate\Http\Request;

use Illuminate\Support\Facades\DB;

class TestController5 extends Controller
{
    public  function stockCostReport(Request $request)
    {
        $filters = $request->only([
            'product_id',
            'unit_id',
            'purchase_invoice_id',
            'date_from',
            'date_to',
        ]);

        $groupByInvoice = $request->boolean('group_by_invoice', false);
        $groupByPrice = $request->boolean('group_by_price', false);

        $reportService = new PurchaseInvoiceProductSummaryReportService();
        $purchasedData = $reportService->getProductSummaryPerInvoice($filters, $groupByInvoice, $groupByPrice);
        return [
            'count' => count($purchasedData),
            'data' => $purchasedData
        ];
    }


    public function orderdData(Request $request)
    {
        $productId = $request->input('product_id');

        $reportService = new PurchaseInvoiceProductSummaryReportService();
        $orderdData = $reportService->getOrderedProductsLinkedToPurchase($productId);
        return [
            'count' => count($orderdData),
            'data' => $orderdData,
        ];
    }

    public function purchasedVSordered(Request $request)
    {
        $reportService = new PurchaseInvoiceProductSummaryReportService();

        $purchased = $reportService->getProductSummaryPerInvoice(); // or with filters
        $ordered = $reportService->getOrderedProductsLinkedToPurchase();

        $diffReport = $reportService->calculatePurchaseVsOrderedDifference($purchased, $ordered);

        return [
            'count' => count($diffReport),
            'data' => $diffReport,
        ];
    }
}
