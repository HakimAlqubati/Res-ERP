<?php

namespace App\Http\Requests\HR\Employee;

use Illuminate\Foundation\Http\FormRequest;

class RejectTerminationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'rejection_reason' => ['required', 'string', 'max:1000'],
        ];
    }
}
