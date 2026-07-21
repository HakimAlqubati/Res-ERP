<?php

namespace App\Modules\Stock\Http\Controllers;

use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

class WrongPackageSizeController extends Controller
{
    public function index(Request $request)
    {
        $sql = <<<'SQL'
SELECT DISTINCT 
    it.store_id,
    s.name AS store_name,
    it.product_id,
    p.name AS product_name,
    
    -- بيانات الإدخال الخاطئ (التي تمت في المخزون)
    it.unit_id AS entered_unit_id,
    u1.name AS entered_unit_name,
    it.package_size AS wrong_package_size,
    it.price AS wrong_price,
    
    -- بيانات الوحدة الصحيحة والمفترضة (بناءً على وحدة الإدخال)
    up.package_size AS correct_package_size,
    up.price AS correct_price,
    
    -- بيانات الوحدة الأساسية (التي حجم عبوتها 1)
    base_up.unit_id AS base_unit_id_size_1,
    u2.name AS base_unit_name,
    base_up.price AS base_unit_price_size_1

FROM inventory_transactions it

-- ربط لجلب السعر والحجم الصحيح للوحدة المُدخلة
INNER JOIN unit_prices up 
    ON it.product_id = up.product_id 
    AND it.unit_id = up.unit_id 
    AND up.deleted_at IS NULL

-- ربط إضافي لجلب الوحدة التي حجمها 1 وسعرها لنفس المنتج
LEFT JOIN unit_prices base_up 
    ON base_up.product_id = it.product_id 
    AND base_up.package_size = 1 
    AND base_up.deleted_at IS NULL

INNER JOIN products p 
    ON p.id = it.product_id 
    AND p.deleted_at IS NULL
    
LEFT JOIN stores s ON s.id = it.store_id
LEFT JOIN units u1 ON u1.id = it.unit_id
LEFT JOIN units u2 ON u2.id = base_up.unit_id

WHERE it.deleted_at IS NULL
  AND it.movement_type = 'in'
  AND it.transactionable_type = 'App\\Models\\StockAdjustmentDetail'
  AND DATE(it.transaction_date) BETWEEN '2026-05-01' AND '2026-06-30'
  AND it.package_size IS NOT NULL    
  AND up.package_size IS NOT NULL
  AND it.package_size != up.package_size
SQL;

        $report = DB::select($sql);

        return view('stock::wrong-package-size.index', compact('report'));
    }
}
