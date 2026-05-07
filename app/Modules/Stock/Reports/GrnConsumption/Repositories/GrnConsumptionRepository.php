<?php

namespace App\Modules\Stock\Reports\GrnConsumption\Repositories;

use App\Models\GoodsReceivedNote;
use App\Models\InventoryTransaction;
use App\Modules\Stock\Reports\GrnConsumption\Contracts\GrnConsumptionRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class GrnConsumptionRepository implements GrnConsumptionRepositoryInterface
{
    public function getPaginatedGrns(array $filters = [], int $perPage = 15): LengthAwarePaginator|Collection
    {
        $query = GoodsReceivedNote::query()
            ->with([
                'inventoryTransactions' => function ($q) {
                    // نجلب فقط حركات الدخول (MOVEMENT_IN) التابعة للسند
                    $q->where('movement_type', InventoryTransaction::MOVEMENT_IN)
                      ->with(['product', 'unit']);
                },
                'purchaseInvoice'
            ]);

        // أمثلة لفلاتر يمكن إضافتها لاحقاً
        if (!empty($filters['grn_number'])) {
            $query->where('grn_number', 'like', '%' . $filters['grn_number'] . '%');
        }

        if ($perPage > 0) {
            return $query->orderBy('id', 'desc')->paginate($perPage);
        }

        return $query->orderBy('id', 'desc')->get();
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
