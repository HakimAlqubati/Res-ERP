<?php

namespace App\Modules\Stock\Reports\ProductGrnAggregation\Contracts;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface ProductAggregationRepositoryInterface
{
    /**
     * Get paginated products that have GRN transactions matching the filters.
     */
    public function getPaginatedProducts(array $filters = [], int $perPage = 15): LengthAwarePaginator;

    /**
     * Get sum of all entry quantities (base units) for the given product IDs.
     */
    public function getInboundAggregations(array $productIds, array $filters = []): array;

    /**
     * Get sum of all consumed quantities (base units) for the given product IDs.
     */
    public function getOutboundAggregations(array $productIds, array $filters = []): array;
}
