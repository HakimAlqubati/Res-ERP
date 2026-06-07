<?php

namespace App\Modules\Stock\Reports\GrnConsumption\Contracts;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

interface GrnConsumptionRepositoryInterface
{
    /**
     * Get paginated GRNs with eager loaded IN inventory transactions.
     *
     * @param array $filters
     * @param int $perPage
     * @return LengthAwarePaginator|Collection
     */
    public function getPaginatedGrns(array $filters = [], int $perPage = 15): LengthAwarePaginator|Collection;

    /**
     * Get all OUT inventory transactions that originated from the given IN transaction IDs.
     *
     * @param array $inboundIds
     * @return Collection
     */
    public function getOutboundTransactionsForInboundIds(array $inboundIds): Collection;
}
