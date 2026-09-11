<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Reports\Orders\OrderShortagePdfReport;
use Illuminate\Http\Request;

class OrderShortageReportController extends Controller
{
    /**
     * Download the order shortage PDF report.
     *
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\StreamedResponse
     */
    public function download(Request $request)
    {
        $orderId = $request->filled('order_id') ? (int) $request->get('order_id') : null;
        $options = [
            'branch_id'  => $request->filled('branch_id') ? (int) $request->get('branch_id') : null,
            'status'     => $request->get('status'),
            'from_order' => $request->filled('from_order') ? (int) $request->get('from_order') : 9662,
            'to_order'   => $request->filled('to_order') ? (int) $request->get('to_order') : 9687,
        ];

        $reportService = new OrderShortagePdfReport();

        return $reportService->download($orderId, $options);
    }

    /**
     * Preview report as HTML in browser.
     *
     * @param Request $request
     * @return \Illuminate\View\View
     */
    public function preview(Request $request)
    {
        $orderId = $request->filled('order_id') ? (int) $request->get('order_id') : null;
        $options = [
            'branch_id'  => $request->filled('branch_id') ? (int) $request->get('branch_id') : null,
            'status'     => $request->get('status'),
            'from_order' => $request->filled('from_order') ? (int) $request->get('from_order') : 9662,
            'to_order'   => $request->filled('to_order') ? (int) $request->get('to_order') : 9687,
        ];

        $reportService = new OrderShortagePdfReport();
        $data = $reportService->getData($orderId, $options);

        return view('reports.orders.order_shortage_pdf', $data);
    }
}
