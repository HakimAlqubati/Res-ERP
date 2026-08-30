<?php

namespace App\Modules\Stock\Reports\StockInventoryValuationReport\DTOs;

class StockInventoryValuationReportDTO
{
    /**
     * @param int $storeId
     * @param string $storeName
     * @param string $inventoryDate
     * @param StockInventoryValuationItemDTO[] $items
     * @param float $grandTotalValue
     * @param int $totalItemsCount
     * @param int $inventoriesCount
     * @param string|null $categoryName
     */
    public function __construct(
        public readonly int $storeId,
        public readonly string $storeName,
        public readonly string $inventoryDate,
        public readonly array $items,
        public readonly float $grandTotalValue,
        public readonly int $totalItemsCount,
        public readonly int $inventoriesCount = 1,
        public readonly ?string $categoryName = null,
    ) {}

    public function toArray(): array
    {
        return [
            'store_id'          => $this->storeId,
            'store_name'        => $this->storeName,
            'inventory_date'    => $this->inventoryDate,
            'category_name'     => $this->categoryName,
            'items'             => array_map(fn($item) => $item->toArray(), $this->items),
            'grand_total_value' => $this->grandTotalValue,
            'total_items_count' => $this->totalItemsCount,
            'inventories_count' => $this->inventoriesCount,
        ];
    }
}
