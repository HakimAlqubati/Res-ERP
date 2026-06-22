<?php

namespace App\Modules\Stock\Reports\OrderTransfersReports\Repositories;

use App\Modules\Stock\Reports\OrderTransfersReports\DTOs\OrderTransferReportFilterDTO;
use App\Modules\Stock\Reports\OrderTransfersReports\Interfaces\OrderTransferReportRepositoryInterface;
use Illuminate\Support\Facades\DB;

class OrderTransferReportRepository implements OrderTransferReportRepositoryInterface
{
    private function prepareQueryComponents(OrderTransferReportFilterDTO $dto): array
    {
        $storeIds = ! empty($dto->branchIds) ? DB::table('branches')->whereIn('id', $dto->branchIds)->pluck('store_id')->filter()->unique()->values()->toArray() : [];
        if (empty($storeIds)) {
            return ['sql_product' => '', 'sql_cat' => '', 'store_placeholders' => '', 'bindings' => [], 'store_ids' => []];
        }

        $bindings = [$dto->toDate, $dto->toDate];

        $productFilterSql = '';
        if ($dto->productId !== null) {
            $productFilterSql = 'AND it_in.product_id = ?';
            $bindings[] = $dto->productId;
        }

        $categoryFilterSql = '';
        if (! empty($dto->categoryIds)) {
            $placeholders = implode(',', array_fill(0, count($dto->categoryIds), '?'));
            $categoryFilterSql = "AND p.category_id IN ($placeholders)";
            $bindings = array_merge($bindings, $dto->categoryIds);
        }

        $storePlaceholders = implode(',', array_fill(0, count($storeIds), '?'));
        $bindings = array_merge($bindings, $storeIds);
        $bindings = array_merge($bindings, [$dto->fromDate, $dto->fromDate, $dto->toDate, $dto->toDate]);

        return [
            'sql_product' => $productFilterSql,
            'sql_cat' => $categoryFilterSql,
            'store_placeholders' => $storePlaceholders,
            'bindings' => $bindings,
            'store_ids' => $storeIds,
        ];
    }

    public function getRawReportData(OrderTransferReportFilterDTO $dto): array
    {
        $components = $this->prepareQueryComponents($dto);
        if (empty($components['store_ids'])) {
            return [];
        }

        $limit = $dto->perPage;
        $offset = ($dto->page - 1) * $dto->perPage;
        $bindings = array_merge($components['bindings'], [$limit, $offset]);

        // ملاحظة: sub_total جاهز هنا تحت اسم remaining_value
        $sql = "
            SELECT 
                b.name AS branch_name, u.name AS unit_name, p.code AS product_code, p.name AS product_name,
                final_t.package_size, final_t.unit_price, final_t.in_qty_base, final_t.out_qty_base, 
                final_t.remaining_qty_unit, final_t.remaining_value
            FROM (
                SELECT 
                    t.store_id, t.unit_id, t.product_id, t.package_size, t.unit_price,
                    SUM(t.in_qty_base) AS in_qty_base, SUM(t.out_qty_base) AS out_qty_base,
                    SUM(t.remaining_qty_unit) AS remaining_qty_unit, SUM(t.remaining_value) AS remaining_value
                FROM (
                    SELECT 
                        it_in.store_id, it_in.unit_id, it_in.product_id, COALESCE(it_in.package_size, 1.0) AS package_size,
                        (it_in.quantity * COALESCE(it_in.package_size, 1.0)) AS in_qty_base,
                        COALESCE(SUM(it_out.quantity * COALESCE(it_out.package_size, 1.0)), 0) AS out_qty_base,
                        GREATEST((it_in.quantity * COALESCE(it_in.package_size, 1.0)) - COALESCE(SUM(it_out.quantity * COALESCE(it_out.package_size, 1.0)), 0), 0) / COALESCE(it_in.package_size, 1.0) AS remaining_qty_unit,
                        CASE WHEN it_in.price IS NULL OR it_in.price = 0 THEN COALESCE(up.price, 0) ELSE it_in.price END AS unit_price,
                        (GREATEST((it_in.quantity * COALESCE(it_in.package_size, 1.0)) - COALESCE(SUM(it_out.quantity * COALESCE(it_out.package_size, 1.0)), 0), 0) / COALESCE(it_in.package_size, 1.0)) * CASE WHEN it_in.price IS NULL OR it_in.price = 0 THEN COALESCE(up.price, 0) ELSE it_in.price END AS remaining_value
                    FROM inventory_transactions AS it_in
                    LEFT JOIN inventory_transactions AS it_out ON it_out.source_transaction_id = it_in.id AND it_out.movement_type = 'out' AND it_out.store_id = it_in.store_id AND it_out.deleted_at IS NULL AND (? IS NULL OR it_out.movement_date <= ?) AND it_out.transactionable_type = 'App\\\\Models\\\\ReturnedOrder'
                    JOIN products p ON p.id = it_in.product_id
                    LEFT JOIN unit_prices up ON up.product_id = it_in.product_id AND up.unit_id = it_in.unit_id
                    WHERE it_in.deleted_at IS NULL AND it_in.movement_type = 'in' AND it_in.store_id IN ({$components['store_placeholders']}) {$components['sql_product']} {$components['sql_cat']} AND (? IS NULL OR it_in.movement_date >= ?) AND (? IS NULL OR it_in.movement_date <= ?) AND it_in.transactionable_type = 'App\\\\Models\\\\Order'
                    GROUP BY it_in.id, it_in.movement_date, it_in.store_id, it_in.unit_id, it_in.product_id, it_in.package_size, it_in.quantity, it_in.price, up.price
                ) AS t
                GROUP BY t.store_id, t.unit_id, t.package_size, t.product_id, t.unit_price
                HAVING SUM(t.remaining_qty_unit) > 0
            ) AS final_t
            JOIN products p ON p.id = final_t.product_id
            LEFT JOIN units u ON u.id = final_t.unit_id
            LEFT JOIN branches b ON b.store_id = final_t.store_id
            ORDER BY b.name, p.code
            LIMIT ? OFFSET ?
        ";

        return DB::select($sql, $bindings);
    }

    /**
     * جلب العدد الكلي والإجمالي المالي الكلي من قاعدة البيانات دفعة واحدة
     */
    public function getReportAggregates(OrderTransferReportFilterDTO $dto): array
    {
        $components = $this->prepareQueryComponents($dto);
        if (empty($components['store_ids'])) {
            return ['total_records' => 0, 'grand_total' => 0.0];
        }

        $sql = "
            SELECT 
                COUNT(*) AS total_records, 
                COALESCE(SUM(final_t.remaining_value), 0) AS grand_total
            FROM (
                SELECT 
                    SUM(t.remaining_qty_unit) AS remaining_qty_unit,
                    SUM(t.remaining_value) AS remaining_value
                FROM (
                    SELECT 
                        it_in.store_id, it_in.unit_id, it_in.product_id, COALESCE(it_in.package_size, 1.0) AS package_size,
                        GREATEST((it_in.quantity * COALESCE(it_in.package_size, 1.0)) - COALESCE(SUM(it_out.quantity * COALESCE(it_out.package_size, 1.0)), 0), 0) / COALESCE(it_in.package_size, 1.0) AS remaining_qty_unit,
                        CASE WHEN it_in.price IS NULL OR it_in.price = 0 THEN COALESCE(up.price, 0) ELSE it_in.price END AS unit_price,
                        (GREATEST((it_in.quantity * COALESCE(it_in.package_size, 1.0)) - COALESCE(SUM(it_out.quantity * COALESCE(it_out.package_size, 1.0)), 0), 0) / COALESCE(it_in.package_size, 1.0)) * CASE WHEN it_in.price IS NULL OR it_in.price = 0 THEN COALESCE(up.price, 0) ELSE it_in.price END AS remaining_value
                    FROM inventory_transactions AS it_in
                    LEFT JOIN inventory_transactions AS it_out ON it_out.source_transaction_id = it_in.id AND it_out.movement_type = 'out' AND it_out.store_id = it_in.store_id AND it_out.deleted_at IS NULL AND (? IS NULL OR it_out.movement_date <= ?) AND it_out.transactionable_type = 'App\\\\Models\\\\ReturnedOrder'
                    JOIN products p ON p.id = it_in.product_id
                    LEFT JOIN unit_prices up ON up.product_id = it_in.product_id AND up.unit_id = it_in.unit_id
                    WHERE it_in.deleted_at IS NULL AND it_in.movement_type = 'in' AND it_in.store_id IN ({$components['store_placeholders']}) {$components['sql_product']} {$components['sql_cat']} AND (? IS NULL OR it_in.movement_date >= ?) AND (? IS NULL OR it_in.movement_date <= ?) AND it_in.transactionable_type = 'App\\\\Models\\\\Order'
                    GROUP BY it_in.id, it_in.movement_date, it_in.store_id, it_in.unit_id, it_in.product_id, it_in.package_size, it_in.quantity, it_in.price, up.price
                ) AS t
                GROUP BY t.store_id, t.unit_id, t.package_size, t.product_id, t.unit_price
                HAVING SUM(t.remaining_qty_unit) > 0
            ) AS final_t
        ";

        $result = DB::select($sql, $components['bindings']);

        return [
            'total_records' => (int) ($result[0]->total_records ?? 0),
            'grand_total' => (float) ($result[0]->grand_total ?? 0.0),
        ];
    }
}
