<?php

namespace App\Modules\Stock\PriceValidation\Repositories;

use App\Modules\Stock\PriceValidation\Contracts\LastPurchasePriceRepositoryInterface;
use App\Modules\Stock\PriceValidation\DTOs\LastPriceRecord;

/**
 * Composite repository that chains multiple price sources.
 *
 * It tries each source in order and returns the first result found.
 * Default chain: PurchaseInvoice → GRN.
 *
 * Usage:
 *   new ChainedPriceRepository([
 *       new PurchaseInvoicePriceRepository(),
 *       new GrnPriceRepository(),
 *   ]);
 */
class ChainedPriceRepository implements LastPurchasePriceRepositoryInterface
{
    /**
     * @param LastPurchasePriceRepositoryInterface[] $sources
     */
    public function __construct(
        private readonly array $sources,
    ) {}

    /**
     * {@inheritDoc}
     */
    public function getLastPrice(int $productId, ?int $unitId = null): ?LastPriceRecord
    {
        foreach ($this->sources as $source) {
            $record = $source->getLastPrice($productId, $unitId);

            if ($record !== null) {
                return $record;
            }
        }

        return null;
    }
}
