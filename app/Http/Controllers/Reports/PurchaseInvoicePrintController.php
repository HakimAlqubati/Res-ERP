<?php
// app/Http/Controllers/PurchaseInvoicePrintController.php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Models\PurchaseInvoice;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PurchaseInvoicePrintController extends Controller
{
    public function show(Request $request, PurchaseInvoice $purchaseInvoice)
    {
        $purchaseInvoice->load([
            'supplier',
            'store',
            'creator',
            'grn',
            'details.product',
            'details.unit',
        ]);

        $items = $purchaseInvoice->details->map(function ($d) {
            return [
                'product'      => $d->product?->name ?? ('#'.$d->product_id),
                'unit'         => $d->unit?->name ?? ('#'.$d->unit_id),
                'quantity'     => (float) $d->quantity,
                'price'        => (float) $d->price,
                'package_size' => $d->package_size,
                'waste_pct'    => $d->waste_stock_percentage,
                'line_total'   => (float) $d->total_price,
            ];
        });

        $subtotal = $items->sum('line_total');

        $meta = [
            'title'              => 'Purchase Invoice #'.$purchaseInvoice->id,
            'invoice_no'         => $purchaseInvoice->invoice_no ?: $purchaseInvoice->id,
            'date'               => optional($purchaseInvoice->date)->format('Y-m-d') ?? now()->format('Y-m-d'),
            'store'              => $purchaseInvoice->store?->name,
            'supplier'           => $purchaseInvoice->supplier?->name,
            'created_by'         => $purchaseInvoice->creator_name,
            'has_grn'            => $purchaseInvoice->has_grn,
            'has_attachment'     => (bool) $purchaseInvoice->has_attachment,
            'has_description'    => (bool) $purchaseInvoice->has_description,
            'cancelled'          => (bool) $purchaseInvoice->cancelled,
            'cancel_reason'      => $purchaseInvoice->cancel_reason,
            'items_count'        => $items->count(),
            'total'              => $subtotal,
            'app_name'           => config('app.name'),
        ];

        $viewHtml = view('purchase_invoices.print', [
            'invoice' => $purchaseInvoice,
            'items'   => $items,
            'meta'    => $meta,
        ])->render();

        if ($request->boolean('download') || $request->boolean('pdf')) {
            $filename = Str::slug($meta['title']).'.pdf';
            return Pdf::loadHTML($viewHtml)
                ->setPaper('a4')
                ->download($filename);
        }

        return response($viewHtml);
    }
}
