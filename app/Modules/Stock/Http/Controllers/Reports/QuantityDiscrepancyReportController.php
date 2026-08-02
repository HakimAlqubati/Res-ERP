<?php

namespace App\Modules\Stock\Http\Controllers\Reports;

use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

class QuantityDiscrepancyReportController extends Controller
{
    public function index(Request $request)
    {
        $sql = <<<'SQL'
WITH base AS (
    SELECT 
        DATE(it.transaction_date) AS movement_date_,
        it.quantity,
        COUNT(it.id) AS count_,
        it.product_id,
        it.transactionable_id AS sctionbleid,
        ROUND(source_in.quantity, 4) AS qin,
        ROUND((out_totals.total_out / source_in.package_size), 4) AS qout
    FROM inventory_transactions it
    LEFT JOIN inventory_transactions source_in 
        ON source_in.id = it.source_transaction_id
    LEFT JOIN (
        SELECT source_transaction_id, SUM(quantity) AS total_out 
        FROM inventory_transactions 
        WHERE deleted_at IS NULL AND movement_type = 'out'
        GROUP BY source_transaction_id
    ) out_totals 
        ON out_totals.source_transaction_id = it.source_transaction_id
    WHERE it.deleted_at IS NULL 
        AND it.movement_type = 'out'
        AND it.transactionable_type = 'App\\Models\\Order'
        AND it.store_id = 1
    GROUP BY 
        it.transactionable_type, it.transactionable_id, it.movement_type,
        it.product_id, it.unit_id, it.package_size, it.store_id, it.price, it.quantity,
        DATE(it.transaction_date), it.source_transaction_id, it.notes,
        source_in.quantity, out_totals.total_out
)
SELECT 
    *,
    ROUND(qout - qin, 4) AS qty_diff
FROM base
WHERE count_ > 1
  AND qout > qin;
SQL;

        $report = DB::select($sql);

        return view('stock::reports.quantity-discrepancy.index', compact('report'));
    }
}
