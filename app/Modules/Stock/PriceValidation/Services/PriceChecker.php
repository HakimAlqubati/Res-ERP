<?php

namespace App\Modules\Stock\PriceValidation\Services;

use App\Modules\Stock\PriceValidation\Contracts\PriceChangeValidatorInterface;
use App\Modules\Stock\PriceValidation\DTOs\PriceCheckItem;
use App\Modules\Stock\PriceValidation\DTOs\PriceCheckResult;

/**
 * Convenience facade for quick single-item price validation.
 *
 * Resolves the validator from the container and returns a
 * pure PriceCheckResult. No UI side-effects — the caller
 * decides what to do with the result.
 *
 * Usage:
 *
 *   $result = PriceChecker::check(productId: 1, unitId: 2, price: 50, packageSize: 1);
 *
 *   if ($result->requiresWarning()) {
 *       // Show notification, throw exception, log, etc.
 *   }
 */
class PriceChecker
{
    /**
     * Validate a single price against historical data.
     *
     * @return PriceCheckResult  Always returns a result — never null.
     */
    public static function check(
        int   $productId,
        int   $unitId,
        float $price,
        float $packageSize = 1,
    ): PriceCheckResult {
        /** @var PriceChangeValidatorInterface $validator */
        $validator = app(PriceChangeValidatorInterface::class);

        $item = new PriceCheckItem(
            productId:   $productId,
            unitId:      $unitId,
            newPrice:    $price,
            packageSize: $packageSize,
        );

        return $validator->validate($item);
    }

    /**
     * Validate multiple items at once.
     *
     * @param  array[] $rows  Array of form rows with keys: product_id, unit_id, price, package_size.
     * @return PriceCheckResult[]
     */
    public static function checkMany(array $rows): array
    {
        /** @var PriceChangeValidatorInterface $validator */
        $validator = app(PriceChangeValidatorInterface::class);

        $items = array_map(
            fn(array $row) => PriceCheckItem::fromFormRow($row),
            $rows,
        );

        return $validator->validateMany($items);
    }
}
