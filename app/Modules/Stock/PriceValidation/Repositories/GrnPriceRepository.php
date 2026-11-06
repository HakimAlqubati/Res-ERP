<?php

namespace App\Modules\Stock\PriceValidation\Repositories;

use App\Models\GoodsReceivedNote;
use App\Models\GoodsReceivedNoteDetail;
use App\Modules\Stock\PriceValidation\Contracts\LastPurchasePriceRepositoryInterface;
use App\Modules\Stock\PriceValidation\DTOs\LastPriceRecord;

/**
 * Fetches the last purchase price from the goods_received_note_details table.
 *
 * Use this implementation when the business prefers to compare
 * against GRN prices rather than purchase invoice prices.
 *
 * To activate: swap the binding in PriceValidationServiceProvider.
 */
class GrnPriceRepository implements LastPurchasePriceRepositoryInterface
{
    /**
     * {@inheritDoc}
     */
    public function getLastPrice(int $productId, ?int $unitId = null): ?LastPriceRecord
    {
        // Fetch the absolute last purchase for this product, regardless of the unit.
        return $this->queryLastDetail($productId);
    }

    /**
     * Query the most recent GRN detail for a product.
     *
     * @param  int $productId
     */
    private function queryLastDetail(int $productId): ?LastPriceRecord
    {
        $query = GoodsReceivedNoteDetail::query()
            ->where('product_id', $productId)
            ->where('price', '>', 0)
            ->whereHas('grn', function ($q) {
                $q->where('status', GoodsReceivedNote::STATUS_APPROVED);
            })
            ->orderByDesc('id');

        $detail = $query->first(['product_id', 'unit_id', 'price', 'package_size', 'grn_id', 'created_at']);

        if (!$detail) {
            return null;
        }

        return new LastPriceRecord(
            productId:   $detail->product_id,
            unitId:      $detail->unit_id,
            price:       (float) $detail->price,
            packageSize: (float) ($detail->package_size ?: 1),
            sourceType:  'grn',
            sourceId:    $detail->grn_id,
            sourceDate:  $detail->created_at?->toDateTimeString(),
        );
    }
}
