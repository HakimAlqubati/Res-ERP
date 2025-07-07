<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\PurchasedReports\PurchaseInvoiceReportService;
use Carbon\Carbon;
use Illuminate\Http\Request;

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
        $storeId     = $request->input('store_id', null);
        $supplierId  = $request->input('supplier_id', null);
        $invoiceNos  = $request->input('invoice_nos', []);
        $categoryIds = $request->input('category_ids', []);
        $perPage     = $request->input('per_page', null);
        $dateFrom    = $request->input('date_from');
        $dateTo      = $request->input('date_to');

        try {
            if ($dateFrom) {
                $dateFrom = Carbon::createFromFormat('d-m-Y', $dateFrom)->format('Y-m-d');
            }
            if ($dateTo) {
                $dateTo = Carbon::createFromFormat('d-m-Y', $dateTo)->format('Y-m-d');
            }
        } catch (\Exception $e) {
            return response()->json(['status' => false, 'message' => 'Invalid date format. Use d-m-Y.']);
        }

        $dateFilter = [
            'from' => $dateFrom,
            'to'   => $dateTo,
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
            'status'                 => true,
            'itemCount'              => $data['results'] instanceof \Illuminate\Pagination\LengthAwarePaginator
            ? $data['results']->total()
            : (is_countable($data['results']) ? count($data['results']) : 0),
            'itemCountInCurrentPage' => is_countable($data['results']) ? count($data['results']) : 0,
            'totalPages'             => $totalPages,
            'totalAmounts'           => $data['total_amount'],
            'data'                   => $data,
        ]);
    }
}