<?php

declare(strict_types=1);

namespace App\Modules\Stock\Http\Requests;

use App\Modules\Stock\Reports\StockBalanceReport\DataTransferObjects\StockBalanceFilterDTO;
use Illuminate\Foundation\Http\FormRequest;

class StockBalanceIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'store_id'       => 'required|integer',
            'category_id'    => 'nullable|integer',
            'product_ids'    => 'nullable|array',
            'product_ids.*'  => 'integer',
            'only_available' => 'nullable|boolean',
            'only_active'    => 'nullable|boolean',
            'per_page'       => 'nullable|integer|min:1|max:100',
        ];
    }

    public function toDTO(): StockBalanceFilterDTO
    {
        return new StockBalanceFilterDTO(
            storeId:       (int) $this->input('store_id'),
            categoryId:    $this->filled('category_id') ? (int) $this->input('category_id') : null,
            productIds:    $this->input('product_ids', []),
            onlyAvailable: (bool) $this->input('only_available', false),
            onlyActive:    (bool) $this->input('only_active', false),
            perPage:       $this->filled('per_page') ? (int) $this->input('per_page') : null,
        );
    }
}
