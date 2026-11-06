<?php

namespace App\Modules\Stock\PriceValidation\Contracts;

use App\Modules\Stock\PriceValidation\DTOs\LastPriceRecord;

/**
 * Contract for retrieving the last purchase price of a product.
 *
 * Each implementation represents a different data source
 * (purchase invoices, GRN details, etc.).
 *
 * Swapping the source is as simple as binding a different
 * implementation in the service provider.
 */
interface LastPurchasePriceRepositoryInterface
{
    /**
     * Retrieve the last recorded purchase price for a product.
     *
     * When $unitId is provided, the repository MUST first attempt
     * to find a record with the same unit. If none exists, it
     * SHOULD look for any unit and return the record with its
     * package_size so the caller can normalise.
     *
     * @param  int      $productId  The product to look up.
     * @param  int|null $unitId     Preferred unit (optional).
     * @return LastPriceRecord|null Null if no purchase history exists.
     */
    public function getLastPrice(int $productId, ?int $unitId = null): ?LastPriceRecord;
}
