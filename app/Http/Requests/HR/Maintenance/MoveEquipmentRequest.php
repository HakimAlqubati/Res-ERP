<?php

namespace App\Http\Requests\HR\Maintenance;

use Illuminate\Foundation\Http\FormRequest;

class MoveEquipmentRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'branch_id' => ['required','integer','exists:branches,id'],
            'branch_area_id' => ['nullable','integer'],
            'description' => ['nullable','string','max:1000'],
        ];
    }
}
