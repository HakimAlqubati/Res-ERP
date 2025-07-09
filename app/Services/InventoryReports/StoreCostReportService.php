<?php
namespace App\Services\InventoryReports;

use App\Models\InventoryTransaction;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class StoreCostReportService
{
    public function __construct(
                protected ?int $storeId,

        protected string $fromDate,
        protected string $toDate,
        protected array $returnableTypes = [], // e.g. [StockReturnOrder::class]
        protected int $perPage = 15,
        protected int $page = 1,
        protected ?int $productId = null,

    ) {}

    public function generate(): LengthAwarePaginator
    {
        $query = InventoryTransaction::query()
            ->select(
                'product_id',
                'base_unit_id',
                DB::raw("SUM(CASE WHEN movement_type = 'in' THEN base_quantity * price_per_base_unit ELSE 0 END) as total_in_cost"),
                DB::raw("SUM(CASE WHEN movement_type = 'out' THEN base_quantity * price_per_base_unit ELSE 0 END) as total_out_cost"),
                DB::raw("SUM(CASE WHEN movement_type = 'in' THEN base_quantity ELSE 0 END) as total_in_qty"),
                DB::raw("SUM(CASE WHEN movement_type = 'out' THEN base_quantity ELSE 0 END) as total_out_qty")
            )
            ->where('store_id', $this->storeId)
            ->when(isset($this->productId) && $this->productId, function ($q) {
                $q->where('product_id', $this->productId);
            })
            ->whereBetween('transaction_date', [$this->fromDate, $this->toDate])
            ->where(function ($q) {
                $q->where('movement_type', InventoryTransaction::MOVEMENT_IN)
                    ->orWhere(function ($q) {
                        $q->where('movement_type', InventoryTransaction::MOVEMENT_OUT)
                            ->whereIn('transactionable_type', $this->returnableTypes)
                        ;
                    });
            })
            ->groupBy('product_id',
                'base_unit_id',
            )
        ;

        $total = $query->count(DB::raw('DISTINCT product_id, base_unit_id'));

        $results = $query
            ->offset(($this->page - 1) * $this->perPage)
            ->limit($this->perPage)
            ->get()
            ->map(function ($row) {
                return [
                    'product'        => $row->product->name,
                    'product_id'     => $row->product_id,
                    'base_unit_id'   => $row->base_unit_id,
                    'base_unit'      => $row?->product?->base_unit_price?->unit?->name,
                    'total_in_cost'  => formatMoneyWithCurrency($row->total_in_cost),
                    'total_out_cost' => formatMoneyWithCurrency($row->total_out_cost),
                    'net_cost'       => formatMoney( $row->total_in_cost - $row->total_out_cost),
                    'total_in_qty'   => formatQuantity2($row->total_in_qty),
                    'total_out_qty'  => formatQuantity2($row->total_out_qty),
                    'net_quantity'   => formatQuantity2($row->total_in_qty - $row->total_out_qty),
                ];
            });

        return new LengthAwarePaginator(
            items: $results,
            total: $total,
            perPage: $this->perPage,
            currentPage: $this->page,
            options: ['path' => request()->url(), 'query' => request()->query()]
        );
    }
}