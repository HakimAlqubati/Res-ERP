<?php

namespace App\Modules\Stock\Reports\ProductGrnAggregation\Services;

use App\Modules\Stock\Reports\ProductGrnAggregation\Contracts\ProductAggregationRepositoryInterface;
use App\Modules\Stock\Reports\ProductGrnAggregation\DTOs\ProductAggregationItemDTO;

class ProductAggregationReportService
{
    public function __construct(
        private readonly ProductAggregationRepositoryInterface $repository
    ) {}

    /**
     * Fetch highly optimized Product-Level GRN Consumption Report.
     */
    public function getReport(array $filters = [], int $perPage = 15)
    {
        // 1. Get paginated products matching the GRN filters (Query 1)
        $paginatedProducts = $this->repository->getPaginatedProducts($filters, $perPage);
        $products = $paginatedProducts->items();
        
        if (empty($products)) {
            return $paginatedProducts;
        }

        // Get array of IDs for the current page
        $productIds = array_map(fn($p) => $p->id, $products);

        // 2. Fetch Base Quantities using purely SQL Aggregations (Queries 2 and 3)
        // No N+1 queries. O(1) performance regardless of data size!
        $inboundTotals = $this->repository->getInboundAggregations($productIds, $filters);
        $outboundTotals = $this->repository->getOutboundAggregations($productIds, $filters);

        $results = [];

        // 3. Map Data
        foreach ($products as $product) {
            $inData = $inboundTotals[$product->id] ?? [];
            $totalIn = (float) ($inData['total_in'] ?? 0);
            $grnsCount = (int) ($inData['grns_count'] ?? 0);
            $unitName = $inData['unit_name'] ?? 'N/A';
            $packageSize = (float) ($inData['package_size'] ?? 1);
            $totalOut = (float) ($outboundTotals[$product->id] ?? 0);
            $remaining = max(0, $totalIn - $totalOut);
            
            $percentage = $totalIn > 0 ? round(($totalOut / $totalIn) * 100, 2) : 0;
            $percentage = min(100, $percentage); // Cap at 100% just in case

            $results[] = new ProductAggregationItemDTO(
                productId: $product->id,
                productName: $product->name ?? 'Unknown',
                productCode: $product->code ?? 'N/A',
                unitName: $unitName,
                packageSize: $packageSize,
                totalEntryQty: round($totalIn, 4),
                totalConsumedQty: round($totalOut, 4),
                remainingQty: round($remaining, 4),
                consumptionPercentage: $percentage,
                isFullyConsumed: ($remaining <= 0 && $totalIn > 0),
                grnsCount: $grnsCount
            );
        }

        // Return standard Laravel Paginator but with our DTOs inside
        return $paginatedProducts->setCollection(collect($results));
    }
}
