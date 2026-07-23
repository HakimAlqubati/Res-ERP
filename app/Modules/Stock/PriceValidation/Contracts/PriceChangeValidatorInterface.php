<?php

namespace App\Modules\Stock\PriceValidation\Contracts;

use App\Modules\Stock\PriceValidation\DTOs\PriceCheckItem;
use App\Modules\Stock\PriceValidation\DTOs\PriceCheckResult;

/**
 * Contract for the price-change validation service.
 *
 * Accepts a price check item and returns a result indicating
 * whether the price change exceeds the configured threshold.
 */
interface PriceChangeValidatorInterface
{
    /**
     * Validate a single line item's price against historical data.
     *
     * @param  PriceCheckItem $item  The item with its new price.
     * @return PriceCheckResult      The validation outcome.
     */
    public function validate(PriceCheckItem $item): PriceCheckResult;

    /**
     * Validate multiple line items in one call.
     *
     * @param  PriceCheckItem[] $items
     * @return PriceCheckResult[]  Indexed by the same keys as the input.
     */
    public function validateMany(array $items): array;

    /**
     * Check if the price validation feature is enabled.
     */
    public function isEnabled(): bool;
}
