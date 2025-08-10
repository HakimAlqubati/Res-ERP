<?php
// app/Http/Requests/HR/Payroll/RunPayrollRequest.php

namespace App\Http\Requests\HR\Payroll;

use Illuminate\Foundation\Http\FormRequest;

class RunPayrollRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // gate/permission if needed
    }

    public function rules(): array
    {
        return [
            'branch_id'          => ['required', 'integer', 'exists:branches,id'],
            'year'               => ['required', 'integer', 'min:2000', 'max:2100'],
            'month'              => ['required', 'integer', 'min:1', 'max:12'],
            'overwrite_existing' => ['sometimes', 'boolean'],
        ];
    }

    public function validatedPayload(): array
    {
        $data = $this->validated();
        unset($data['employee_ids']); // safety: enforce not provided
        return $data;
    }
}
