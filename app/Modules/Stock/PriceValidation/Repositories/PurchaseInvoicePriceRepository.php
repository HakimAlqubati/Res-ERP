<?php

namespace App\Modules\Stock\PriceValidation\Repositories;

use App\Models\PurchaseInvoiceDetail;
use App\Modules\Stock\PriceValidation\Contracts\LastPurchasePriceRepositoryInterface;
use App\Modules\Stock\PriceValidation\DTOs\LastPriceRecord;

/**
 * Fetches the last purchase price from the purchase_invoice_details table.
 *
 * This is the primary (default) source for historical pricing data.
 * It looks up the most recent purchase invoice detail that contains
 * the given product, preferring the same unit when available.
 */
class PurchaseInvoicePriceRepository implements LastPurchasePriceRepositoryInterface
{
    /**
     * {@inheritDoc}
     */
    public function getLastPrice(int $productId, ?int $unitId = null): ?LastPriceRecord
    {
        // 1. Try same product + same unit first (most accurate).
        if ($unitId) {
            $record = $this->queryLastDetail($productId, $unitId);

            if ($record) {
                return $record;
            }
        }

        // 2. Fallback: any unit for this product (will be normalised via package_size).
        return $this->queryLastDetail($productId);
    }

    /**
     * Query the most recent purchase invoice detail for a product.
     *
     * @param  int      $productId
     * @param  int|null $unitId  When null, matches any unit.
     */
    private function queryLastDetail(int $productId, ?int $unitId = null): ?LastPriceRecord
    {
        $query = PurchaseInvoiceDetail::query()
            ->where('product_id', $productId)
            ->where('price', '>', 0)
            ->orderByDesc('id');

        if ($unitId !== null) {
            $query->where('unit_id', $unitId);
        }

        $detail = $query->first(['product_id', 'unit_id', 'price', 'package_size', 'purchase_invoice_id', 'created_at']);

        if (!$detail) {
            return null;
        }

        return new LastPriceRecord(
            productId:   $detail->product_id,
            unitId:      $detail->unit_id,
            price:       (float) $detail->price,
            packageSize: (float) ($detail->package_size ?: 1),
            sourceType:  'purchase_invoice',
            sourceId:    $detail->purchase_invoice_id,
            sourceDate:  $detail->created_at?->toDateString(),
        );
    }
}
