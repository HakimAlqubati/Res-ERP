<?php

namespace App\Http\Requests\HR\Maintenance;

use Illuminate\Foundation\Http\FormRequest;

class StoreServiceRequestRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'description'        => ['required','string','max:2000'],
            'branch_id'          => ['required','integer','exists:branches,id'],
            'branch_area_id'     => ['nullable','integer','exists:branch_areas,id'],
            'employee_ids'       => ['nullable','array'],
            'employee_ids.*'     => ['integer','exists:hr_employees,id'],
            'primary_employee_id'=> ['nullable','integer','exists:hr_employees,id'],
            'urgency'            => ['required','in:High,Medium,Low'],
            'impact'             => ['required','in:High,Medium,Low'],
            'status'             => ['nullable','in:New,Pending,In progress,Closed'],
            'equipment_id'       => ['nullable','integer','exists:hr_equipment,id'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'status' => $this->input('status') ?: \App\Models\ServiceRequest::STATUS_NEW,
        ]);
    }
}
