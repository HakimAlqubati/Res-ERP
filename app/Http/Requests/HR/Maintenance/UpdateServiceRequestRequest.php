<?php

namespace App\Http\Requests\HR\Maintenance;

use Illuminate\Foundation\Http\FormRequest;

class UpdateServiceRequestRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'description'        => ['sometimes','string','max:2000'],
            'branch_id'          => ['sometimes','integer','exists:branches,id'],
            'branch_area_id'     => ['sometimes','nullable','integer','exists:branch_areas,id'],
            'employee_ids'       => ['sometimes','nullable','array'],
            'employee_ids.*'     => ['integer','exists:hr_employees,id'],
            'primary_employee_id'=> ['sometimes','nullable','integer','exists:hr_employees,id'],
            'urgency'            => ['sometimes','in:High,Medium,Low'],
            'impact'             => ['sometimes','in:High,Medium,Low'],
            'status'             => ['sometimes','in:New,Pending,In progress,Closed'],
            'equipment_id'       => ['sometimes','nullable','integer','exists:hr_equipment,id'],
            'accepted'           => ['sometimes','boolean'],
        ];
    }
}
