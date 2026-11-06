<?php

namespace App\Modules\Stock\Reports\StockInventoryValuationReport\DTOs;

class StockInventoryValuationItemDTO
{
    public function __construct(
        public readonly int $productId,
        public readonly string $productCode,
        public readonly string $productName,
        public readonly ?int $unitId,
        public readonly string $unitName,
        public readonly float $packageSize,
        public readonly float $physicalQty,
        public readonly float $unitPrice,
        public readonly float $totalValue,
        public readonly string $priceSource = 'none',
    ) {}

    public function toArray(): array
    {
        return [
            'product_id'   => $this->productId,
            'product_code' => $this->productCode,
            'product_name' => $this->productName,
            'unit_id'      => $this->unitId,
            'unit_name'    => $this->unitName,
            'package_size' => $this->packageSize,
            'physical_qty' => $this->physicalQty,
            'unit_price'   => $this->unitPrice,
            'total_value'  => $this->totalValue,
            'price_source' => $this->priceSource,
        ];
    }
}
