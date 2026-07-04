<?php

namespace App\Modules\Stock\Http\Controllers;

use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

class QuantityDiscrepancyController extends Controller
{
    public function index(Request $request)
    {
        $storeId = $request->input('store_id');
        $report = [];

        if ($storeId) {
            $sql = <<<'SQL'
WITH base AS (
    SELECT 
        DATE(it.transaction_date) AS out_date,
        DATE(source_in.transaction_date) AS in_date,
        it.quantity,
        COUNT(it.id) AS count_,
        it.product_id,
        p.name AS product_name,
        it.transactionable_type AS out_type,
        it.transactionable_id AS out_id,
        source_in.transactionable_type AS in_type,
        source_in.transactionable_id AS in_id,
        ROUND(source_in.quantity, 4) AS qin,
        ROUND((out_totals.total_out / source_in.package_size), 4) AS qout
    FROM inventory_transactions it
    LEFT JOIN products p ON p.id = it.product_id
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
        AND it.store_id = :store_id
    GROUP BY 
        it.transactionable_type, it.transactionable_id, it.movement_type,
        source_in.transactionable_type, source_in.transactionable_id,
        it.product_id, p.name, it.unit_id, it.package_size, it.store_id, it.price, it.quantity,
        DATE(it.transaction_date), DATE(source_in.transaction_date), it.source_transaction_id, it.notes,
        source_in.quantity, source_in.package_size, out_totals.total_out
)
SELECT 
    *,
    ROUND(qout - qin, 4) AS qty_diff
FROM base
WHERE count_ > 1
  AND qout > qin;
SQL;

            $report = DB::select($sql, ['store_id' => $storeId]);
        }

        return view('stock::quantity-discrepancy.index', compact('report', 'storeId'));
    }
}
