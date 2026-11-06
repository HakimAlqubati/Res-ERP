<?php

namespace App\Modules\Stock\PriceValidation\DTOs;

/**
 * Immutable value object representing a single line item
 * whose price needs to be validated against historical data.
 */
final class PriceCheckItem
{
    public function __construct(
        public readonly int   $productId,
        public readonly int   $unitId,
        public readonly float $newPrice,
        public readonly float $packageSize,
    ) {}

    /**
     * Factory: create from a repeater row (e.g. Filament form state).
     *
     * @param array{product_id: int, unit_id: int, price: float, package_size: float} $row
     */
    public static function fromFormRow(array $row): self
    {
        return new self(
            productId:   (int)   ($row['product_id']   ?? 0),
            unitId:      (int)   ($row['unit_id']      ?? 0),
            newPrice:    (float) ($row['price']         ?? 0),
            packageSize: (float) ($row['package_size']  ?? 1),
        );
    }

    /**
     * Normalise the new price to the smallest base unit.
     *
     * Example: price=100, packageSize=20 → 100/20 = 5 per base unit.
     */
    public function normalizedNewPrice(): float
    {
        return $this->packageSize > 0
            ? $this->newPrice / $this->packageSize
            : $this->newPrice;
    }

    public function isValid(): bool
    {
        return $this->productId > 0
            && $this->unitId > 0
            && $this->newPrice > 0;
    }
}
