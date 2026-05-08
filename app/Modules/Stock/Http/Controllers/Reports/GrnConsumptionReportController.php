<?php

namespace App\Modules\Stock\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Modules\Stock\Reports\GrnConsumption\Services\GrnConsumptionReportService;
use Illuminate\Http\Request;

class GrnConsumptionReportController extends Controller
{
    public function __construct(
        private readonly GrnConsumptionReportService $reportService
    ) {}

    /**
     * Display the GRN Consumption Report.
     */
    public function index(Request $request)
    {
        // Allow filtering by GRN Number and Smart Filters
        $filters = $request->only([
            'grn_number',
            'search',
            'date_from',
            'date_to',
            'store_id',
            'supplier_id',
            'invoice_status',
            'has_attachment',
            'has_notes',
            'status',
            'product_id',
            'older_than_days',
            'completion_status'
        ]);
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

        // Fetch stores for mandatory filter
        $stores = \App\Models\Store::select('id', 'name')->get();

        // Load the view from the stock module's views directory
        return view('stock::reports.grn-consumption.index', compact('report', 'filters', 'selectedProduct', 'stores'));
    }

    /**
     * Display the flattened GRN Consumption Report (Products as rows).
     */
    public function flattenedIndex(Request $request)
    {
        // Allow all smart filters
        $filters = $request->only([
            'search',
            'grn_number',
            'date_from',
            'date_to',
            'store_id',
            'supplier_id',
            'invoice_status',
            'has_attachment',
            'has_notes',
            'status',
            'product_id',
            'older_than_days',
            'completion_status'
        ]);
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
            $report = $this->reportService->getFlattenedReport($filters, 15);
        }

        // Fetch selected product name for the autocomplete input
        $selectedProduct = null;
        if (!empty($filters['product_id'])) {
            $selectedProduct = \App\Models\Product::find($filters['product_id']);
        }

        return view('stock::reports.grn-consumption.flattened', compact('report', 'filters', 'selectedProduct', 'stores'));
    }

    /**
     * API endpoint to search products for the filter dropdown.
     */
    public function searchProducts(Request $request)
    {
        $query = \App\Models\Product::select('id', 'name', 'code')->limit(10);

        if ($search = $request->get('q')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%");
            });
        }

        return response()->json($query->get());
    }
}
