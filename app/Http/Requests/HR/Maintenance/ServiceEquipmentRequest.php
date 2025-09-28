<?php

namespace App\Http\Requests\HR\Maintenance;

use Illuminate\Foundation\Http\FormRequest;

class ServiceEquipmentRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'serviced_at' => ['nullable','date'],
            'description' => ['nullable','string','max:1000'],
        ];
    }
}
