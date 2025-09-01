<?php
namespace App\Http\Controllers\Api;

use Exception;
use App\Models\PurchaseInvoiceDetail;
use App\Http\Controllers\Controller;
use App\Models\PurchaseInvoice;
use Carbon\Carbon;
use Illuminate\Http\Request;

class PurchaseInvoiceController extends Controller
{
    /**
     * Get list of purchase invoices (summary only, no details)
     */
    public function index(Request $request)
    {
        $perPage = $request->input('per_page', 15);
        $query   = PurchaseInvoice::query()
            ->with(['supplier:id,name', 'store:id,name', 'paymentMethod:id,name'])
            ->select(
                'id',
                'invoice_no',
                'supplier_id',
                'store_id',
                'date',
                'payment_method_id',
                'attachment',
                'date'
            );

        // 🔍 Filters
        if ($request->filled('supplier_id')) {
            $query->where('supplier_id', $request->supplier_id);
        }

        if ($request->filled('payment_method_id')) {
            $query->where('payment_method_id', $request->payment_method_id);
        }

        if ($request->filled('from_date')) {
            try {
                $fromDate = Carbon::createFromFormat('d-m-Y', $request->from_date)->format('Y-m-d');
                $query->whereDate('date', '>=', $fromDate);
            } catch (Exception $e) {
                return response()->json(['success' => false, 'message' => 'Invalid from_date format. Use d-m-Y.']);
            }
        }

        if ($request->filled('to_date')) {
            try {
                $toDate = Carbon::createFromFormat('d-m-Y', $request->to_date)->format('Y-m-d');
                $query->whereDate('date', '<=', $toDate);
            } catch (Exception $e) {
                return response()->json(['success' => false, 'message' => 'Invalid to_date format. Use d-m-Y.']);
            }
        }
        $invoiceIds = (clone $query)->pluck('id');

// جلب جميع التفاصيل الخاصة بهذه الفواتير
        $details = PurchaseInvoiceDetail::whereIn('purchase_invoice_id', $invoiceIds)->get();

// جمع إجمالي السعر المحسوب
        $totalAmount = $details->sum(fn($detail) => $detail->quantity * $detail->price);
        // $invoices = $query->latest()->get();
        $paginator = $query->latest()->paginate($perPage);
        // 🧾 Format output
        $data = $paginator->getCollection()->map(function ($invoice) {

            return [
                'invoice_no'     => $invoice->invoice_no,
                'supplier'       => $invoice->supplier?->name,
                'store'          => $invoice->store?->name,
                'details_count'  => $invoice->details_count,
                'total_amount'   => formatMoneyWithCurrency($invoice->total_amount),
                'has_attachment' => $invoice->has_attachment ? 'Yes' : 'No',
                'date'           => $invoice->date,
            ];
        });
        $paginator->setCollection($data);
        return response()->json([
            'success'                => true,
            'data'                   => $data,
            'total_pages'            => $paginator->lastPage(),
            'current_page'           => $paginator->currentPage(),
            'per_page'               => $paginator->perPage(),
            'total'                  => $paginator->total(),
            'raw_total_amount'       => $totalAmount,
            'formatted_total_amount' => formatMoneyWithCurrency($totalAmount),
        ]);
    }
}