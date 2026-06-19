<?php

declare(strict_types=1);

namespace App\Modules\Stock\Http\Requests;

use App\Modules\Stock\Reports\FifoBatchReports\DataTransferObjects\StockBatchFilterDTO;
use Illuminate\Foundation\Http\FormRequest;

class StockBatchIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'store_id'       => 'required|integer|exists:stores,id',
            'product_ids'    => 'nullable|array',
            'product_ids.*'  => 'integer|exists:products,id',
            'current_batch'  => 'nullable|boolean',
            'per_page'       => 'nullable|integer|min:1|max:100',
        ];
    }

    public function toDTO(): StockBatchFilterDTO
    {
        return new StockBatchFilterDTO(
            storeId:        (int) $this->input('store_id'),
            productIds:     array_map('intval', $this->input('product_ids', [])),
            isCurrentBatch: $this->filled('current_batch')
                ? (bool) $this->input('current_batch')
                : null,
           perPage: (int) $this->input('per_page', 20),
        );
    }
}
