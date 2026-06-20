<?php

declare(strict_types=1);

namespace App\Modules\Stock\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StockBalanceShowRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'store_id' => 'required|integer',
        ];
    }

    public function storeId(): int
    {
        return (int) $this->input('store_id');
    }
}
