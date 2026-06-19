<?php

declare(strict_types=1);

namespace App\Modules\Stock\Reports\FifoBatchReports\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class StockBatchResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'product_id' => $this->product_id,
            'product_name' => $this->product,

            'document' => $this->source_document,
            'transactionable_type' => $this->transactionable_type,
            'transactionable_id' => $this->transactionable_id,
            'movement_date' => $this->movement_date,

            'original_unit' => $this->unit,
            'original_in_qty' => (float) $this->in_qty,
            'package_size' => (float) $this->package_size,
            'base_unit' => $this->base_unit,
            'base_unit_package_size' => (float) $this->base_unit_package_size,

            'base_unit_in_qty' => (float) $this->base_unit_in_qty,
            'base_unit_out_qty' => (float) $this->base_unit_out,
            'current_stock' => (float) $this->current_stock,

            'original_price' => (float) $this->price,
            'unit_price' => (float) $this->unit_price,
            'remaining_total_price' => (float) $this->remaining_total_price,

            'is_current_batch' => (bool) $this->is_current_batch,
        ];
    }
}
