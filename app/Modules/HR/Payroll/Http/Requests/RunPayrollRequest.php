<?php

namespace App\Modules\HR\Payroll\Http\Requests;

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
            'pay_date'           => ['sometimes', 'nullable', 'date'],
            'employee_ids'       => ['sometimes', 'array'],
            'employee_ids.*'     => ['integer', 'exists:hr_employees,id'],
        ];
    }

    public function validatedPayload(): array
    {
        return $this->validated();
    }
}
