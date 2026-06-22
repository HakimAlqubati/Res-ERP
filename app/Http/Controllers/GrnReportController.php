<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class GrnReportController extends Controller
{
    public function index(Request $request)
    {
        $selectedDate = $request->input('date', '2026-06-11');

        $count = DB::table('goods_received_notes as grn')
            ->whereNull('grn.deleted_at')
            ->where('grn.status', 'approved')
            ->where('grn.cancelled', 0)
            ->whereNull('grn.purchase_invoice_id')
            ->whereDate('grn.created_at', '<=', $selectedDate)
            ->count('grn.id');

        return view('reports.grn_count', compact('count', 'selectedDate'));
    }
}
