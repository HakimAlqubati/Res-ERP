<?php

namespace App\Modules\Stock\PriceValidation\DTOs;

/**
 * Immutable value object representing the last known purchase
 * price for a product, retrieved from any historical source.
 */
final class LastPriceRecord
{
    public function __construct(
        public readonly int    $productId,
        public readonly int    $unitId,
        public readonly float  $price,
        public readonly float  $packageSize,
        public readonly string $sourceType,   // e.g. 'purchase_invoice', 'grn'
        public readonly ?int   $sourceId = null,
        public readonly ?string $sourceDate = null,
    ) {}

    /**
     * Normalise the historical price to the smallest base unit.
     *
     * Example: price=100, packageSize=20 → 100/20 = 5 per base unit.
     */
    public function normalizedPrice(): float
    {
        return $this->packageSize > 0
            ? $this->price / $this->packageSize
            : $this->price;
    }
}
