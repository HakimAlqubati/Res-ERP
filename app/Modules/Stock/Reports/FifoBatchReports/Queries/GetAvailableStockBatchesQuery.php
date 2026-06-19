<?php

declare(strict_types=1);

namespace App\Modules\Stock\Reports\FifoBatchReports\Queries;

use App\Models\UnitPrice;
use App\Modules\Stock\Reports\FifoBatchReports\Contracts\GetAvailableStockBatchesQueryInterface;
use App\Modules\Stock\Reports\FifoBatchReports\DataTransferObjects\StockBatchFilterDTO;
use App\Modules\Stock\Reports\FifoBatchReports\DataTransferObjects\StockBatchReportResult;
 use Illuminate\Database\Query\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Pagination\Paginator;
final class GetAvailableStockBatchesQuery implements GetAvailableStockBatchesQueryInterface
{
    private const TABLE = 'inventory_transactions';

    public function execute(StockBatchFilterDTO $filters): StockBatchReportResult
    {
        // بناء الاستعلام بالتدريج باستخدام Subqueries مدعومة أصلياً في لارافل
        // يتم تمرير المخرجات من طبقة إلى أخرى لضمان مقروئية الكود وسهولة تتبعه
        $rawBatches = $this->rawBatchesSubquery($filters);
        $layer1     = $this->layer1RunningTotal($rawBatches);
        $layer2     = $this->layer2MaxPrev($layer1);
        $layer3     = $this->layer3Filtered($layer2);
        
        // الطبقة النهائية
        $query = $this->layer4Final($layer3);

        // تطبيق فلتر الباتش الحالي إن وُجد
        // يجب تغليف الاستعلام في subquery لأن MySQL لا يسمح بفلترة عمود ناتج عن window function في نفس المستوى
        if ($filters->isCurrentBatch !== null) {
            $query = DB::query()
                ->fromSub($query, 'batch_with_flag')
                ->select('*')
                ->where('is_current_batch', $filters->isCurrentBatch ? 1 : 0);
        }

        // --- حساب الملخص الإجمالي (All Summary) بحركة ذكية ---
         // نطلب من قاعدة البيانات جلب العدد والمجموع في استعلام واحد فقط!
        $aggregates = DB::query()
            ->fromSub(clone $query, 'summary_table')
            ->selectRaw('
                COUNT(*) as total_batches, 
                COALESCE(SUM(remaining_total_price), 0) as total_price
            ')->first();

        $totalBatches = (int) $aggregates->total_batches;
        $totalPrice   = (float) $aggregates->total_price;

        $query->orderBy('product_id', 'asc')->orderBy('id', 'asc');

        if ($filters->wantsPagination()) {
            $page = Paginator::resolveCurrentPage();
            // نجلب بيانات هذه الصفحة فقط
            $results = $query->forPage($page, $filters->perPage)->get();

            // نبني الـ Paginator يدوياً ونمرر له العدد الذي حسبناه مسبقاً لكي لا يكرر عملية العد
        $batches = new \Illuminate\Pagination\Paginator(
    $results,
    $filters->perPage,
    $page,
    ['path' => Paginator::resolveCurrentPath()]
);
$batches->hasMorePagesWhen($totalBatches > ($page * $filters->perPage));

         }else{
            $batches = $query->get();
         }
        return new StockBatchReportResult($batches, $totalBatches, $totalPrice);
        return $query->get();
    }

    /**
     * ترتيب الوحدات لجلب الوحدة الأساسية.
     */
    private function rankedUnitsSubquery(StockBatchFilterDTO $filters): Builder
    {
        return DB::table('unit_prices as up')
            ->select('up.product_id', 'u.name as base_unit_name', 'up.package_size as base_package_size')
            ->selectRaw('ROW_NUMBER() OVER(PARTITION BY up.product_id ORDER BY up.package_size ASC) as rn')
            ->join('units as u', 'up.unit_id', '=', 'u.id')
            ->whereIn('up.usage_scope', [
                UnitPrice::USAGE_ALL,
                UnitPrice::USAGE_SUPPLY_ONLY,
                UnitPrice::USAGE_OUT_ONLY,
                UnitPrice::USAGE_NONE,
            ])
            ->when($filters->hasProductFilter(), fn ($q) => $q->whereIn('up.product_id', $filters->productIds));
    }

    /**
     * جلب الوحدة الأساسية باستخدام fromSub.
     */
    private function baseUnitsSubquery(StockBatchFilterDTO $filters): Builder
    {
        return DB::query()
            ->fromSub($this->rankedUnitsSubquery($filters), 'ranked_units')
            ->where('rn', 1);
    }

    /**
     * تجميع الكميات المنصرفة (Out).
     */
    private function outAggregatesSubquery(StockBatchFilterDTO $filters): Builder
    {
        return DB::table(self::TABLE)
            ->select('source_transaction_id')
            ->selectRaw('SUM(quantity * package_size) AS total_out_qty')
            ->where('movement_type', 'out')
            ->where('store_id', $filters->storeId)
            ->whereNull('deleted_at')
            ->when($filters->hasProductFilter(), fn ($q) => $q->whereIn('product_id', $filters->productIds))
            ->groupBy('source_transaction_id');
    }

    /**
     * جلب الحركات الواردة (In) وربطها مع المنصرف والوحدات باستخدام leftJoinSub.
     */
    private function rawBatchesSubquery(StockBatchFilterDTO $filters): Builder
    {
        return DB::table(self::TABLE . ' AS in_t')
            ->select([
                'in_t.id', 'in_t.product_id', 'p.name as product','p.code as product_code', 'in_t.transactionable_type',
                'in_t.transactionable_id', 'in_t.movement_date', 'in_t.unit_id', 'u.name as unit',
                'in_t.quantity as in_qty', 'in_t.package_size', 'bu.base_unit_name as base_unit',
                'bu.base_package_size as base_unit_package_size', 'in_t.price'
            ])
            ->selectRaw('(in_t.quantity * in_t.package_size) AS base_unit_in_qty')
            ->selectRaw('COALESCE(oa.total_out_qty, 0) AS base_unit_out')
            ->selectRaw('(in_t.price / in_t.package_size) as unit_price')
            ->leftJoinSub($this->outAggregatesSubquery($filters), 'oa', 'oa.source_transaction_id', '=', 'in_t.id')
            ->leftJoinSub($this->baseUnitsSubquery($filters), 'bu', 'bu.product_id', '=', 'in_t.product_id')
            ->join('products AS p', 'in_t.product_id', '=', 'p.id')
            ->join('units AS u', 'in_t.unit_id', '=', 'u.id')
            ->where('in_t.movement_type', 'in')
            ->where('in_t.store_id', $filters->storeId)
            ->whereNull('in_t.deleted_at')
            ->when($filters->hasProductFilter(), fn ($q) => $q->whereIn('in_t.product_id', $filters->productIds));
    }

    /**
     * الطبقة 1: حساب الرصيد الخام والمجموع التراكمي.
     */
    private function layer1RunningTotal(Builder $source): Builder
    {
        return DB::query()
            ->fromSub($source, 'cte_raw_batches')
            ->selectRaw('*')
            ->selectRaw('(base_unit_in_qty - base_unit_out) AS current_stock')
            ->selectRaw('SUM(base_unit_in_qty - base_unit_out) OVER (PARTITION BY product_id ORDER BY id) AS running_total')
            ->whereRaw('(base_unit_in_qty - base_unit_out) != 0');
    }

    /**
     * الطبقة 2: حساب أعلى مجموع تراكمي سابق.
     */
    private function layer2MaxPrev(Builder $source): Builder
    {
        return DB::query()
            ->fromSub($source, 'cte_layer1_rt')
            ->selectRaw('*')
            ->selectRaw('COALESCE(MAX(running_total) OVER (PARTITION BY product_id ORDER BY id ROWS BETWEEN UNBOUNDED PRECEDING AND 1 PRECEDING), 0) AS max_prev_rt');
    }

    /**
     * الطبقة 3: تصفية الباتشات الموجبة وحساب الرصيد الفعلي.
     */
    private function layer3Filtered(Builder $source): Builder
    {
        return DB::query()
            ->fromSub($source, 'cte_layer2_max_prev')
            ->selectRaw('*')
            ->selectRaw('GREATEST(0, running_total - GREATEST(0, max_prev_rt)) AS real_current_stock')
            ->whereRaw('current_stock > 0')
            ->whereRaw('GREATEST(0, running_total - GREATEST(0, max_prev_rt)) > 0');
    }

    /**
     * الطبقة 4: تحضير النتيجة النهائية وتحديد الباتش الحالي.
     */
    private function layer4Final(Builder $source): Builder
    {
        return DB::query()
            ->fromSub($source, 'cte_layer3_filtered')
            ->select([
                'id', 'product_id', 'product', 'product_code', 'transactionable_type', 'transactionable_id', 'movement_date',
                'unit', 'in_qty', 'package_size', 'base_unit', 'base_unit_package_size', 'price',
                'base_unit_in_qty', 'base_unit_out', 'unit_price',
            ])
            ->selectRaw('real_current_stock AS current_stock')
            ->selectRaw('(real_current_stock * unit_price) AS remaining_total_price')
            ->selectRaw('CASE WHEN ROW_NUMBER() OVER (PARTITION BY product_id ORDER BY id) = 1 THEN 1 ELSE 0 END AS is_current_batch')
            ->selectRaw('CONCAT(transactionable_id, " #", transactionable_type) AS source_document');
    }
}