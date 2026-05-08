<?php

namespace App\Modules\Stock\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Modules\Stock\Reports\ProductGrnAggregation\Services\ProductAggregationReportService;
use Illuminate\Http\Request;

class ProductGrnAggregationReportController extends Controller
{
    public function __construct(
        private readonly ProductAggregationReportService $reportService
    ) {}

    /**
     * Display the Product-Level GRN Aggregation Report.
     */
    public function index(Request $request)
    {
        // Allow smart filtering
        $filters = $request->only(['search', 'date_from', 'date_to', 'store_id', 'completion_status', 'invoice_status']);
        
        // Fetch stores for mandatory filter (ONLY default store)
        $stores = \App\Models\Store::select('id', 'name')
            ->where('default_store', true)
            ->active()
            ->get();

        $defaultStore = $stores->first();
        if ($defaultStore) {
            $filters['store_id'] = $defaultStore->id;
            $request->merge(['store_id' => $defaultStore->id]);
        }

        // Require store_id to fetch data
        if (empty($filters['store_id'])) {
            $report = new \Illuminate\Pagination\LengthAwarePaginator([], 0, 15);
        } else {
            // Fetch paginated report (15 items per page)
            $report = $this->reportService->getReport($filters, 15);
        }

        // Fetch selected product name for the autocomplete input
        $selectedProduct = null;
        if (!empty($filters['product_id'])) {
            $selectedProduct = \App\Models\Product::find($filters['product_id']);
        }

        // Load the view
        return view('stock::reports.product-grn-aggregation.index', compact('report', 'filters', 'selectedProduct', 'stores'));
    }
}
