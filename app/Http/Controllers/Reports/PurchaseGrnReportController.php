<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Models\PurchaseInvoice;
use App\Models\GoodsReceivedNote;

class PurchaseGrnReportController extends Controller
{
    public function index()
    {
        // الفواتير
        $totalInvoices       = PurchaseInvoice::count(); // يستثني المحذوفة منطقياً تلقائياً
        $invoicesWithGrn     = PurchaseInvoice::whereHas('grn')->count(); // GRN غير محذوفة
        $invoicesWithoutGrn  = $totalInvoices - $invoicesWithGrn;
        $pctInvoicesWithGrn  = $totalInvoices ? round(($invoicesWithGrn / $totalInvoices) * 100, 2) : 0;

        // GRN
        $totalGrn            = GoodsReceivedNote::count();
        $grnLinkedToInvoice  = GoodsReceivedNote::whereNotNull('purchase_invoice_id')->count();
        $grnWithoutInvoice   = $totalGrn - $grnLinkedToInvoice;
        $pctGrnLinked        = $totalGrn ? round(($grnLinkedToInvoice / $totalGrn) * 100, 2) : 0;

        return view('reports.purchase-grn', [
            'invoices' => [
                'total'      => $totalInvoices,
                'linked'     => $invoicesWithGrn,
                'unlinked'   => $invoicesWithoutGrn,
                'pct_linked' => $pctInvoicesWithGrn,
            ],
            'grn' => [
                'total'      => $totalGrn,
                'linked'     => $grnLinkedToInvoice,
                'unlinked'   => $grnWithoutInvoice,
                'pct_linked' => $pctGrnLinked,
            ],
        ]);
    }
}
