<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\PurchasedReports\PurchaseInvoiceReportService;

class PurchaseReportController extends Controller
{
    protected PurchaseInvoiceReportService $service;

    public function __construct(PurchaseInvoiceReportService $service)
    {
        $this->service = $service;
    }

    public function index(Request $request)
    {
        $productsIds = $request->input('product_ids', []);
        $storeId = $request->input('store_id', null);
        $supplierId = $request->input('supplier_id', null);
        $invoiceNos = $request->input('invoice_nos', []);
        $categoryIds = $request->input('category_ids', []);
        $perPage = $request->input('per_page', null);
        $dateFilter = [
            'from' => $request->input('date_from'),
            'to'   => $request->input('date_to'),
        ];

        $data = $this->service->getPurchasesInvoiceDataWithPagination(
            $productsIds,
            $storeId,
            $supplierId,
            $invoiceNos,
            $dateFilter,
            $categoryIds,
            $perPage
        );
        $totalPages = $data['results'] instanceof \Illuminate\Pagination\LengthAwarePaginator
            ? $data['results']->lastPage()
            : 1;
        return response()->json([
            'status' => true,
            'totalPages' => $totalPages,
            'data' => $data,
        ]);
    }
}