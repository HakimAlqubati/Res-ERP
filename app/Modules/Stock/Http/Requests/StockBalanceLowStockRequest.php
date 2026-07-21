<?php

declare(strict_types=1);

namespace App\Modules\Stock\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StockBalanceLowStockRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'store_id' => 'required|integer',
            'per_page' => 'nullable|integer|min:1|max:100',
        ];
    }

    public function storeId(): int
    {
        return (int) $this->input('store_id');
    }

    public function perPage(): int
    {
        return $this->filled('per_page') ? (int) $this->input('per_page') : 15;
    }
}
