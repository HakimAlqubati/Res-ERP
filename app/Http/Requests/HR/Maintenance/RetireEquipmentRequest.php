<?php

namespace App\Http\Requests\HR\Maintenance;

use Illuminate\Foundation\Http\FormRequest;

class RetireEquipmentRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'description' => ['nullable','string','max:1000'],
        ];
    }
}
