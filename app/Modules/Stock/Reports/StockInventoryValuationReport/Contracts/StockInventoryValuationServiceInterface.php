<?php

namespace App\Modules\Stock\Reports\StockInventoryValuationReport\Contracts;

use App\Modules\Stock\Reports\StockInventoryValuationReport\DTOs\StockInventoryValuationReportDTO;

interface StockInventoryValuationServiceInterface
{
    /**
     * Generate the aggregated Stock Inventory Valuation Report for a given store and date.
     *
     * @param int $storeId
     * @param string $inventoryDate
     * @param int|null $categoryId
     * @return StockInventoryValuationReportDTO|null
     */
    public function getReport(int $storeId, string $inventoryDate, ?int $categoryId = null): ?StockInventoryValuationReportDTO;

    /**
     * Get distinct inventory dates available for a store.
     *
     * @param int $storeId
     * @return array<string, string>
     */
    public function getAvailableDatesByStore(int $storeId): array;
}
