<?php

namespace App\Http\Requests\HR\Employee;

use Illuminate\Foundation\Http\FormRequest;

class StoreTerminationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'termination_date'   => ['required', 'date'],
            'termination_reason' => ['required', 'string', 'max:1000'],
            'notes'              => ['nullable', 'string', 'max:2000'],
        ];
    }
}
