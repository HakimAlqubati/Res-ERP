<?php

namespace App\Http\Controllers\Api\Reports;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\PurchasedReports\PurchaseInvoiceProductSummaryReportService;
use Illuminate\Pagination\LengthAwarePaginator;

class PurchaseInventoryReportController extends Controller
{
    public function index(Request $request)
    {
        $productId   = $request->input('product_id');
        $storeId     = $request->input('store_id');
        $productType = $request->input('manufacturing_filter'); // 'only_mana' or 'only_unmana'
        $categoryId  = $request->input('category_id');

        $reportService = new PurchaseInvoiceProductSummaryReportService();

        $filters = [
            'product_id'       => $productId,
            'store_id'         => $storeId,
        ];

        if ($productType === 'only_mana') {
            $filters['only_manufacturing'] = 1;
        } elseif ($productType === 'only_unmana') {
            $filters['only_unmanufacturing'] = 1;
        }

        if ($categoryId) {
            $filters['category_id'] = $categoryId;
        }

        // استخراج البيانات
        $purchased  = $reportService->getProductSummaryPerInvoice($filters);
        $ordered    = $reportService->getOrderedProductsLinkedToPurchase($filters);
        $diffReport = $reportService->calculatePurchaseVsOrderedDifference($purchased, $ordered, $storeId);

        // تحويل إلى Collection
        $collection = collect($diffReport);

        $allTotalPrice = $collection->sum('price');


        // إعداد Pagination
        $page     = $request->input('page', 1);
        $perPage  = $request->input('per_page', 15);
        $total    = $collection->count();
        $results  = $collection->slice(($page - 1) * $perPage, $perPage)->values();

        $paginator = new LengthAwarePaginator(
            $results,
            $total,
            $perPage,
            $page,
            ['path' => url()->current(), 'query' => $request->query()]
        );

        // إضافة all_total_price في الاستجابة
        $response = $paginator->toArray();
        $response['total'] = formatMoneyWithCurrency($allTotalPrice) ;

        return response()->json($response);
        return response()->json($paginator);
    }
}
