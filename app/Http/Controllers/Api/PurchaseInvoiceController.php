<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PurchaseInvoice;
use Illuminate\Http\Request;

class PurchaseInvoiceController extends Controller
{
    /**
     * Get list of purchase invoices (summary only, no details)
     */
    public function index(Request $request)
    {
        $perPage = $request->input('per_page', 15);
        $query = PurchaseInvoice::query()
            ->with(['supplier:id,name', 'store:id,name', 'paymentMethod:id,name'])
            ->select('id', 'invoice_no', 'supplier_id', 'store_id', 'date', 'payment_method_id', 'attachment');

        // 🔍 Filters
        if ($request->filled('supplier_id')) {
            $query->where('supplier_id', $request->supplier_id);
        }

        if ($request->filled('payment_method_id')) {
            $query->where('payment_method_id', $request->payment_method_id);
        }

        if ($request->filled('from_date')) {
            $query->whereDate('date', '>=', $request->from_date);
        }

        if ($request->filled('to_date')) {
            $query->whereDate('date', '<=', $request->to_date);
        }

        // $invoices = $query->latest()->get();
        $paginator = $query->latest()->paginate($perPage);
        // 🧾 Format output
        $data = $paginator->getCollection()->map(function ($invoice) {

            return [
                'invoice_no' => $invoice->invoice_no,
                'supplier' => $invoice->supplier?->name,
                'store' => $invoice->store?->name,
                'details_count' => $invoice->details_count,
                'total_amount' => number_format($invoice->total_amount, 2),
                'has_attachment' => $invoice->has_attachment ? 'Yes' : 'No',
            ];
        });
        $paginator->setCollection($data);
        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }
}
