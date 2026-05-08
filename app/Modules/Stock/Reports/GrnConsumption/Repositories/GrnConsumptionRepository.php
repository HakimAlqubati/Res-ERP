<?php

namespace App\Modules\Stock\Reports\GrnConsumption\Repositories;

use App\Models\GoodsReceivedNote;
use App\Models\InventoryTransaction;
use App\Modules\Stock\Reports\GrnConsumption\Contracts\GrnConsumptionRepositoryInterface;
use App\Modules\Stock\Reports\GrnConsumption\Filters\GrnConsumptionFilter;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class GrnConsumptionRepository implements GrnConsumptionRepositoryInterface
{
    public function __construct(
        private readonly GrnConsumptionFilter $filter
    ) {}

    public function getPaginatedGrns(array $filters = [], int $perPage = 15): LengthAwarePaginator|Collection
    {
        $completionStatus = $filters['completion_status'] ?? \App\Modules\Stock\Reports\Enums\FilterCompletionStatus::ALL->value;

        $query = GoodsReceivedNote::query()
            ->with([
                'inventoryTransactions' => function ($q) use ($completionStatus, $filters) {
                    // نجلب فقط حركات الدخول (MOVEMENT_IN) التابعة للسند
                    $q->where('movement_type', InventoryTransaction::MOVEMENT_IN)
                      ->with(['product', 'unit']);
                      
                    if (!empty($filters['product_id'])) {
                        $productIds = is_array($filters['product_id']) ? $filters['product_id'] : [$filters['product_id']];
                        $q->whereIn('product_id', $productIds);
                    }
                      
                    $outType = InventoryTransaction::MOVEMENT_OUT;
                    if ($completionStatus === \App\Modules\Stock\Reports\Enums\FilterCompletionStatus::INCOMPLETE->value) {
                        $q->whereRaw("(quantity * package_size) > (
                            SELECT COALESCE(SUM(out_tx.quantity * out_tx.package_size), 0)
                            FROM inventory_transactions out_tx
                            WHERE out_tx.source_transaction_id = inventory_transactions.id
                            AND out_tx.movement_type = '{$outType}'
                            AND out_tx.deleted_at IS NULL
                        )");
                    } elseif ($completionStatus === \App\Modules\Stock\Reports\Enums\FilterCompletionStatus::COMPLETED->value) {
                        $q->whereRaw("(quantity * package_size) <= (
                            SELECT COALESCE(SUM(out_tx.quantity * out_tx.package_size), 0)
                            FROM inventory_transactions out_tx
                            WHERE out_tx.source_transaction_id = inventory_transactions.id
                            AND out_tx.movement_type = '{$outType}'
                            AND out_tx.deleted_at IS NULL
                        )");
                    }
                },
                'purchaseInvoice'
            ]);

        // تطبيق الفلترة الذكية
        $query = $this->filter->apply($query, $filters);

        if ($perPage > 0) {
            return $query->paginate($perPage);
        }

        return $query->get();
    }

    public function getOutboundTransactionsForInboundIds(array $inboundIds): Collection
    {
        if (empty($inboundIds)) {
            return new Collection();
        }

        // نجلب كل حركات الخروج (MOVEMENT_OUT) التي تشير إلى حركات الدخول المحددة
        // من خلال حقل source_transaction_id
        return InventoryTransaction::query()
            ->where('movement_type', InventoryTransaction::MOVEMENT_OUT)
            ->whereIn('source_transaction_id', $inboundIds)
            ->get(['id', 'quantity', 'package_size', 'source_transaction_id', 'movement_type']);
    }
}
