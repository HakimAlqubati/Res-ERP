<?php

namespace App\Http\Requests\HR\Maintenance;

use Illuminate\Foundation\Http\FormRequest;

class StoreEquipmentLogRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'action' => ['required','in:Created,Updated,Serviced,Moved,Retired,Note'],
            'description' => ['nullable','string','max:1000'],
        ];
    }
}
