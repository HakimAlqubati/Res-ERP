<?php

namespace App\Modules\Stock\Reports\OrderTransfersReports\Repositories;

use App\Modules\Stock\Reports\OrderTransfersReports\DTOs\OrderTransferReportFilterDTO;
use App\Modules\Stock\Reports\OrderTransfersReports\Interfaces\OrderTransferReportRepositoryInterface;
use Illuminate\Support\Facades\DB;

class OrderTransferReportRepository implements OrderTransferReportRepositoryInterface
{
    /**
     * تنفيذ الاستعلام وجلب البيانات الخام بأعلى أداء
     */
    public function getRawReportData(OrderTransferReportFilterDTO $dto): array
    {
        // 1. جلب معرفات المخازن (Store IDs) المرتبطة بالفروع المحددة
        $storeIds = [];
        if (!empty($dto->branchIds)) {
            $storeIds = DB::table('branches')
                ->whereIn('id', $dto->branchIds)
                ->pluck('store_id')
                ->filter()
                ->unique()
                ->values()
                ->toArray();
        }

        // إذا لم تكن هناك مخازن مرتبطة، نوقف الاستعلام فوراً لتوفير الموارد
        if (empty($storeIds)) {
            return [];
        }

        // 2. تحضير متغيرات الربط (Bindings) والشروط (Conditions)
        // نبدأ بمتغيرات تاريخ الإخراج (it_out)
        $bindings = [$dto->toDate, $dto->toDate]; 
        
        $productFilterSql = '';
        if ($dto->productId !== null) {
            $productFilterSql = "AND it_in.product_id = ?";
            $bindings[] = $dto->productId;
        }

        $categoryFilterSql = '';
        if (!empty($dto->categoryIds)) {
            $placeholders = implode(',', array_fill(0, count($dto->categoryIds), '?'));
            $categoryFilterSql = "AND p.category_id IN ($placeholders)";
            $bindings = array_merge($bindings, $dto->categoryIds);
        }

        // متغيرات المخازن (it_in)
        $storePlaceholders = implode(',', array_fill(0, count($storeIds), '?'));
        $bindings = array_merge($bindings, $storeIds);

        // متغيرات التواريخ للدخول (it_in)
        $bindings = array_merge($bindings, [$dto->fromDate, $dto->fromDate, $dto->toDate, $dto->toDate]);

        // 3. بناء استعلام الـ SQL المحسّن
        // نستخدم التجميع الداخلي (Inner Grouping) باستخدام الأرقام فقط، 
        // ثم التجميع الخارجي (Outer Joins) لجلب النصوص والأسماء.
        $sql = "
            SELECT 
                b.name AS branch_name,
                u.name AS unit_name,
                p.code AS product_code,
                p.name AS product_name,
                final_t.package_size,
                final_t.unit_price,
                final_t.in_qty_base,
                final_t.out_qty_base,
                final_t.remaining_qty_unit,
                final_t.remaining_value
            FROM (
                SELECT 
                    t.store_id,
                    t.unit_id,
                    t.product_id,
                    t.package_size,
                    t.unit_price,
                    SUM(t.in_qty_base)  AS in_qty_base,
                    SUM(t.out_qty_base) AS out_qty_base,
                    SUM(t.remaining_qty_unit) AS remaining_qty_unit,
                    SUM(t.remaining_value)    AS remaining_value
                FROM (
                    SELECT
                        it_in.store_id,
                        it_in.unit_id,
                        it_in.product_id,
                        COALESCE(it_in.package_size, 1.0) AS package_size,
                        
                        -- حساب كميات الدخول
                        (it_in.quantity * COALESCE(it_in.package_size, 1.0)) AS in_qty_base,
                        
                        -- حساب كميات الخروج (المرتجعات)
                        COALESCE(SUM(it_out.quantity * COALESCE(it_out.package_size, 1.0)), 0) AS out_qty_base,
                        
                        -- حساب الكمية المتبقية
                        GREATEST(
                            (it_in.quantity * COALESCE(it_in.package_size, 1.0)) 
                            - COALESCE(SUM(it_out.quantity * COALESCE(it_out.package_size, 1.0)), 0), 
                            0
                        ) / COALESCE(it_in.package_size, 1.0) AS remaining_qty_unit,
                        
                        -- حساب السعر
                        CASE 
                            WHEN it_in.price IS NULL OR it_in.price = 0 THEN COALESCE(up.price, 0)
                            ELSE it_in.price
                        END AS unit_price,
                        
                        -- حساب القيمة المتبقية
                        (
                            GREATEST(
                                (it_in.quantity * COALESCE(it_in.package_size, 1.0)) 
                                - COALESCE(SUM(it_out.quantity * COALESCE(it_out.package_size, 1.0)), 0), 
                                0
                            ) / COALESCE(it_in.package_size, 1.0)
                        ) * CASE 
                            WHEN it_in.price IS NULL OR it_in.price = 0 THEN COALESCE(up.price, 0)
                            ELSE it_in.price
                        END AS remaining_value

                    FROM inventory_transactions AS it_in
                    
                    -- الانضمام مع جدول المرتجعات
                    LEFT JOIN inventory_transactions AS it_out
                        ON it_out.source_transaction_id = it_in.id
                        AND it_out.movement_type = 'out'
                        AND it_out.store_id = it_in.store_id
                        AND it_out.deleted_at IS NULL
                        AND (? IS NULL OR it_out.movement_date <= ?)
                        AND it_out.transactionable_type = 'App\\\\Models\\\\ReturnedOrder'
                        
                    -- الانضمام مع جدول المنتجات (فقط للفلترة بالقسم إن وجد)
                    JOIN products p ON p.id = it_in.product_id
                    
                    -- الانضمام مع أسعار الوحدات
                    LEFT JOIN unit_prices up
                        ON up.product_id = it_in.product_id
                        AND up.unit_id    = it_in.unit_id

                    WHERE it_in.deleted_at IS NULL
                        AND it_in.movement_type = 'in'
                        AND it_in.store_id IN ($storePlaceholders)
                        {$productFilterSql}
                        {$categoryFilterSql}
                        AND (? IS NULL OR it_in.movement_date >= ?)
                        AND (? IS NULL OR it_in.movement_date <= ?)
                        AND it_in.transactionable_type = 'App\\\\Models\\\\Order'

                    GROUP BY
                        it_in.id, it_in.movement_date, it_in.store_id, it_in.unit_id, 
                        it_in.product_id, it_in.package_size, it_in.quantity, it_in.price, up.price
                ) AS t
                GROUP BY 
                    t.store_id, t.unit_id, t.package_size, t.product_id, t.unit_price
            ) AS final_t
            
            -- الربط النهائي لجلب النصوص لعرضها في التقرير
            JOIN products p ON p.id = final_t.product_id
            LEFT JOIN units u ON u.id = final_t.unit_id
            LEFT JOIN branches b ON b.store_id = final_t.store_id
            
            -- تجاهل السجلات التي رصيدها صفر
            WHERE final_t.remaining_qty_unit > 0
            ORDER BY b.name, p.code 
        ";

        // تنفيذ الاستعلام وإرجاع النتيجة خام
        return DB::select($sql, $bindings);
    }
}