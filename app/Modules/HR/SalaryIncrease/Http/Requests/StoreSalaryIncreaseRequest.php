<?php

declare(strict_types=1);

namespace App\Modules\HR\SalaryIncrease\Http\Requests;

use App\Models\SalaryIncreaseRequest;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSalaryIncreaseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'employee_id'         => ['required', 'integer', 'exists:hr_employees,id'],
            'increase_type'       => ['required', 'string', Rule::in(SalaryIncreaseRequest::INCREASE_TYPES)],
            'increase_amount'     => ['required_if:increase_type,fixed', 'nullable', 'numeric', 'gt:0'],
            'increase_percentage' => ['required_if:increase_type,percentage', 'nullable', 'numeric', 'gt:0', 'max:500'],
            'new_salary'          => ['required_if:increase_type,new_salary', 'nullable', 'numeric', 'gt:0'],
            'effective_date'      => ['nullable', 'date'],
            'notes'               => ['nullable', 'string', 'max:2000'],
        ];
    }
}
