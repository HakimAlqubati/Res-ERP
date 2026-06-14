<?php

namespace App\Modules\Stock\Reports\FifoBatchReport\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Modules\Stock\Reports\FifoBatchReport\DTOs\FifoBatchFilterDTO;

class FifoBatchFilterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'product_id' => 'nullable|integer|exists:products,id',
            'unit_id'    => 'nullable|integer|exists:units,id',
            'store_id'   => 'nullable|integer|exists:stores,id',
            'date_from'  => 'nullable|date',
            'date_to'    => 'nullable|date|after_or_equal:date_from',
        ];
    }

    public function toFilterDTO(): FifoBatchFilterDTO
    {
        return FifoBatchFilterDTO::fromArray($this->validated());
    }
}
