<?php

namespace App\Http\Controllers\Api\Reports;

use App\Http\Controllers\Controller;
use App\Services\PurchasedReports\GoodsReceivedNoteReportService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Mccarlosen\LaravelMpdf\Facades\LaravelMpdf as PDF;

class GoodsReceivedNoteReportController extends Controller
{
    /**
     * GET /api/reports/grn
     *
     * Query params (all optional):
     * - per_page: int or "all" (default 15; "all" -> 9999)
     * - product_id[]: int[]
     * - grn_number[]: string[]
     * - supplier_id: int | "all"
     * - store_id: int | "all"
     * - category_id[]: int[]
     * - date_from: Y-m-d
     * - date_to:   Y-m-d
     * - show_grn_number: bool
     */
    public function index(Request $request)
    {
        // Validate inputs (keep types loose where "all" is accepted)
        $validated = $request->validate([
            'per_page'                 => ['nullable', 'string'], // allow "all"
            'page'                     => ['nullable', 'integer', 'min:1'],

            'product_id'               => ['nullable', 'array'],
            'product_id.*'             => ['integer'],

            'grn_number'               => ['nullable', 'array'],
            'grn_number.*'             => ['string'],

            'supplier_id'              => ['nullable'], // "all" or int
            'store_id'                 => ['nullable'], // "all" or int

            'category_id'              => ['nullable', 'array'],
            'category_id.*'            => ['integer'],

            'date_from'                => ['nullable', 'date'],
            'date_to'                  => ['nullable', 'date', 'after_or_equal:date_from'],

            'show_grn_number'          => ['nullable'], // bool-ish
        ]);

        // Per-page handling
        $perPageParam = $request->query('per_page', 15);
        $perPage = ($perPageParam === 'all') ? 9999 : (int) $perPageParam;
        if ($perPage <= 0) {
            $perPage = 15;
        }

        // Filters (defaults match your Filament page)
        $productsIds = (array) $request->query('product_id', []);
        $grnNumbers  = (array) $request->query('grn_number', []);
        $supplierId  = $request->query('supplier_id', 'all');
        $storeId     = $request->query('store_id', 'all');
        $categoryIds = (array) $request->query('category_id', []);

        // Map to the date structure your service expects
        $dateRange = [];
        $dateFrom  = $request->query('date_from');
        $dateTo    = $request->query('date_to');
        if ($dateFrom || $dateTo) {
            // If your service expects ['start' => ..., 'end' => ...]
            $dateRange = ['start' => $dateFrom, 'end' => $dateTo];
        }

        $showGrnNumber = filter_var($request->query('show_grn_number', false), FILTER_VALIDATE_BOOLEAN);

        // Fetch data via your existing service
        $service = new GoodsReceivedNoteReportService();
        $data    = $service->getGrnDataWithPagination(
            $productsIds,
            $storeId,
            $supplierId,
            $grnNumbers,
            $dateRange,
            $categoryIds,
            $perPage
        );

        // Build a safe, consistent response (works whether your service returns a paginator-like array or a plain array)
        $response = [
            'data'           => $data,
            'total_amount'       => $data['total_amount']       ?? null,
            'final_total_amount' => $data['final_total_amount'] ?? null,
            'show_grn_number'    => $showGrnNumber,
        ];

        // If your service returns pagination meta, surface it explicitly too
        // Try common keys; ignore if not present
        $pagination = [
            'current_page' => $data['current_page'] ?? ($data['meta']['current_page'] ?? null),
            'per_page'     => $perPage,
            'last_page'    => $data['last_page'] ?? ($data['meta']['last_page'] ?? null),
            'total'        => $data['total'] ?? ($data['meta']['total'] ?? null),
        ];
        if ($pagination['current_page'] !== null) {
            $response['pagination'] = $pagination;
        }

        return response()->json($response);
    }

    /**
     * GET /api/reports/grn.pdf
     * Same filters as index(); streams a PDF.
     */
    public function exportPdf(Request $request)
    {
        // Reuse the same input parsing
        $request->validate([
            'per_page'                 => ['nullable', 'string'],
            'product_id'               => ['nullable', 'array'],
            'product_id.*'             => ['integer'],
            'grn_number'               => ['nullable', 'array'],
            'grn_number.*'             => ['string'],
            'supplier_id'              => ['nullable'],
            'store_id'                 => ['nullable'],
            'category_id'              => ['nullable', 'array'],
            'category_id.*'            => ['integer'],
            'date_from'                => ['nullable', 'date'],
            'date_to'                  => ['nullable', 'date', 'after_or_equal:date_from'],
            'show_grn_number'          => ['nullable'],
        ]);

        $perPageParam = $request->query('per_page', 'all'); // export defaults to "all"
        $perPage = ($perPageParam === 'all') ? 9999 : (int) $perPageParam;
        if ($perPage <= 0) $perPage = 9999;

        $productsIds   = (array) $request->query('product_id', []);
        $grnNumbers    = (array) $request->query('grn_number', []);
        $supplierId    = $request->query('supplier_id', 'all');
        $storeId       = $request->query('store_id', 'all');
        $categoryIds   = (array) $request->query('category_id', []);
        $dateFrom      = $request->query('date_from');
        $dateTo        = $request->query('date_to');
        $dateRange     = ($dateFrom || $dateTo) ? ['start' => $dateFrom, 'end' => $dateTo] : [];
        $showGrnNumber = filter_var($request->query('show_grn_number', false), FILTER_VALIDATE_BOOLEAN);

        $service = new GoodsReceivedNoteReportService();
        $data    = $service->getGrnDataWithPagination(
            $productsIds,
            $storeId,
            $supplierId,
            $grnNumbers,
            $dateRange,
            $categoryIds,
            $perPage
        );

        $pdf = PDF::loadView('export.reports.grn-report', [
            'grn_data'        => $data,
            'show_grn_number' => $showGrnNumber,
        ]);

        return response()->streamDownload(function () use ($pdf) {
            $pdf->stream('grn-report.pdf');
        }, 'grn-report.pdf');
    }
}
