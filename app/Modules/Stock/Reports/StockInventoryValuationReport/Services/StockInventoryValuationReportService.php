<?php

namespace App\Modules\Stock\Reports\StockInventoryValuationReport\Services;

use App\Modules\Stock\Reports\StockInventoryValuationReport\Contracts\StockInventoryValuationRepositoryInterface;
use App\Modules\Stock\Reports\StockInventoryValuationReport\Contracts\StockInventoryValuationServiceInterface;
use App\Modules\Stock\Reports\StockInventoryValuationReport\DTOs\StockInventoryValuationItemDTO;
use App\Modules\Stock\Reports\StockInventoryValuationReport\DTOs\StockInventoryValuationReportDTO;
use App\Services\Financial\ClosingStockCalculationService;

class StockInventoryValuationReportService implements StockInventoryValuationServiceInterface
{
    public function __construct(
        private readonly StockInventoryValuationRepositoryInterface $repository,
        private readonly ClosingStockCalculationService $closingStockCalculationService,
    ) {}

    /**
     * {@inheritDoc}
     */
    public function getReport(int $storeId, string $inventoryDate): ?StockInventoryValuationReportDTO
    {
        $inventories = $this->repository->getInventoriesByStoreAndDate($storeId, $inventoryDate);

        if ($inventories->isEmpty()) {
            return null;
        }

        $storeName = $inventories->first()?->store?->name ?? 'Store #' . $storeId;
        $aggregatedRows = [];

        foreach ($inventories as $inventory) {
            $details = $this->closingStockCalculationService->getDetailedClosingStockValues($inventory);

            foreach ($details as $row) {
                $key = $row['product_id'] . '_' . ($row['unit_name'] ?? '');

                if (! isset($aggregatedRows[$key])) {
                    $aggregatedRows[$key] = [
                        'product_id'   => (int) $row['product_id'],
                        'product_code' => (string) ($row['product_code'] ?? '—'),
                        'product_name' => (string) ($row['product_name'] ?? '—'),
                        'unit_id'      => isset($row['unit_id']) ? (int) $row['unit_id'] : null,
                        'unit_name'    => (string) ($row['unit_name'] ?? '—'),
                        'package_size' => (float) ($row['package_size'] ?? 1),
                        'physical_qty' => (float) ($row['physical_qty'] ?? 0),
                        'unit_price'   => (float) ($row['unit_price'] ?? 0),
                        'total_value'  => (float) ($row['total_value'] ?? 0),
                        'price_source' => (string) ($row['price_source'] ?? 'none'),
                    ];
                } else {
                    $newQty   = $aggregatedRows[$key]['physical_qty'] + (float) ($row['physical_qty'] ?? 0);
                    $newValue = $aggregatedRows[$key]['total_value'] + (float) ($row['total_value'] ?? 0);

                    $aggregatedRows[$key]['physical_qty'] = $newQty;
                    $aggregatedRows[$key]['total_value']  = $newValue;
                    $aggregatedRows[$key]['unit_price']   = $newQty > 0 ? ($newValue / $newQty) : (float) ($row['unit_price'] ?? 0);
                }
            }
        }

        // Sort rows alphabetically by product name
        uasort($aggregatedRows, fn($a, $b) => strcasecmp($a['product_name'], $b['product_name']));

        $items = [];
        $grandTotalValue = 0.0;

        foreach ($aggregatedRows as $row) {
            $grandTotalValue += $row['total_value'];

            $items[] = new StockInventoryValuationItemDTO(
                productId: $row['product_id'],
                productCode: $row['product_code'],
                productName: $row['product_name'],
                unitId: $row['unit_id'],
                unitName: $row['unit_name'],
                packageSize: $row['package_size'],
                physicalQty: $row['physical_qty'],
                unitPrice: $row['unit_price'],
                totalValue: $row['total_value'],
                priceSource: $row['price_source'],
            );
        }

        return new StockInventoryValuationReportDTO(
            storeId: $storeId,
            storeName: $storeName,
            inventoryDate: $inventoryDate,
            items: $items,
            grandTotalValue: $grandTotalValue,
            totalItemsCount: count($items),
            inventoriesCount: $inventories->count(),
        );
    }

    /**
     * {@inheritDoc}
     */
    public function getAvailableDatesByStore(int $storeId): array
    {
        return $this->repository->getAvailableDatesByStore($storeId);
    }
}
