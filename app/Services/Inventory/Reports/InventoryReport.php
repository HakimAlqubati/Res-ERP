<?php

namespace App\Services\Inventory\Reports;

use App\Services\Inventory\Contracts\{
    InventoryReportInterface,
    InventoryRepositoryInterface,
    PricingStrategyInterface,
    UnitConversionInterface,
    ThresholdPolicyInterface
};
use App\Services\Inventory\Dto\{InventoryFiltersDTO, InventoryRowDTO, PaginationDTO};
use App\Services\Inventory\Domain\InventoryAggregator;

final class InventoryReport implements InventoryReportInterface
{
    public function __construct(
        private InventoryRepositoryInterface $repo,
        private PricingStrategyInterface $pricing,
        private UnitConversionInterface $units,
        private ThresholdPolicyInterface $thresholds,
        private InventoryAggregator $aggregator,
    ) {}

    /** @return array{data: InventoryRowDTO[], pagination: ?PaginationDTO} */
    public function run(InventoryFiltersDTO $filters): array
    {
        // dd($filters);
        // 1) جلب خام
        $movements = $this->repo->fetchMovements($filters); // in/out per product/unit/store

        // dd($movements,$filters);
        // 2) تجميع إلى IN/OUT/Base
        $summary = $this->aggregator->summarize($movements, $this->units);

        // dd($summary);
        // 3) تسعير
        $priced = $this->pricing->apply($summary, $filters->storeId);
// dd($priced);
        // 4) حد أدنى
        $finalRows = $this->thresholds->decorate($priced);
        // dd($finalRows);
        // 5) Pagination إن احتجت
        $pagination = $this->repo->pagination();

        return ['data' => $finalRows, 'pagination' => $pagination];
    }
}
