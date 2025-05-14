<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use Illuminate\Support\Facades\DB;

class TestController5 extends Controller
{

    public function testo()
    {
        $results = DB::table('inventory_transactions as it')
            ->join('products as p', 'it.product_id', '=', 'p.id')
            ->join('units as u', 'it.unit_id', '=', 'u.id')
            ->selectRaw('
                p.id AS product_id,
                p.name AS product_name,
                u.id AS unit_id,
                u.name AS unit_name,
    
                SUM(CASE
                    WHEN it.movement_type = "in" AND it.transactionable_type = "App\\\\Models\\\\PurchaseInvoice"
                    THEN it.quantity
                    ELSE 0
                END) AS total_in,
    
                SUM(CASE
                    WHEN it.movement_type = "out"
                         AND it.transactionable_type = "App\\\\Models\\\\Order"
                         AND it.source_transaction_id IN (
                            SELECT DISTINCT it1.source_transaction_id
                            FROM inventory_transactions it1
                            WHERE it1.transactionable_type = "App\\\\Models\\\\Order"
                              AND EXISTS (
                                  SELECT 1
                                  FROM inventory_transactions it2
                                  WHERE it2.id = it1.source_transaction_id
                                    AND it2.transactionable_type = "App\\\\Models\\\\PurchaseInvoice"
                              )
                         )
                    THEN it.quantity
                    ELSE 0
                END) AS total_out,
    
                SUM(CASE
                    WHEN it.movement_type = "in" AND it.transactionable_type = "App\\\\Models\\\\PurchaseInvoice"
                    THEN it.quantity
                    ELSE 0
                END) -
    
                SUM(CASE
                    WHEN it.movement_type = "out"
                         AND it.transactionable_type = "App\\\\Models\\\\Order"
                         AND it.source_transaction_id IN (
                            SELECT DISTINCT it1.source_transaction_id
                            FROM inventory_transactions it1
                            WHERE it1.transactionable_type = "App\\\\Models\\\\Order"
                              AND EXISTS (
                                  SELECT 1
                                  FROM inventory_transactions it2
                                  WHERE it2.id = it1.source_transaction_id
                                    AND it2.transactionable_type = "App\\\\Models\\\\PurchaseInvoice"
                              )
                         )
                    THEN it.quantity
                    ELSE 0
                END) AS net_quantity
            ')
            ->groupBy('p.id', 'p.name', 'u.id', 'u.name')
            ->having('total_in', '>', 0)
            ->get();

        $grouped = collect($results)->groupBy('product_id');
        $converted = [];

        foreach ($grouped as $productId => $entries) {
            // Find the row with largest package size for this product
            $max = null;
            $maxPackageSize = -1;

            foreach ($entries as $row) {
                $unitPrice = \App\Models\UnitPrice::where('product_id', $row->product_id)
                    ->where('unit_id', $row->unit_id)
                    ->orderByDesc('package_size')
                    ->first();

                if ($unitPrice && $unitPrice->package_size > $maxPackageSize) {
                    $max = $row;
                    $maxPackageSize = $unitPrice->package_size;
                    $row->unit_price = $unitPrice->price;
                    $row->package_size = $unitPrice->package_size;
                    $row->largest_unit = $unitPrice->unit->name ?? $row->unit_name;
                }
            }

            if ($max) {
                $netQty = round($max->net_quantity / $max->package_size, 2);
                if ($netQty > 0) {
                    $converted[] = [
                        'product_id'    => $max->product_id,
                        'product_name'  => $max->product_name,
                        'largest_unit'  => $max->largest_unit,
                        'package_size'  => $max->package_size,
                        'total_in'      => round($max->total_in / $max->package_size, 2),
                        'total_out'     => round($max->total_out / $max->package_size, 2),
                        'net_quantity'  => $netQty,
                        'unit_price'    => $max->unit_price,
                    ];
                }
            }
        }

        return $converted;
        $finalResult = [
            'count' => count($converted),
            'data' => $converted,
        ];

        return response()->json($finalResult);
    }
}
