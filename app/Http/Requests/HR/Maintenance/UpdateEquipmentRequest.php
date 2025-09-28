<?php

namespace App\Http\Requests\HR\Maintenance;

use Illuminate\Foundation\Http\FormRequest;

class UpdateEquipmentRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'asset_tag' => ['sometimes','string','max:255'],
            'name' => ['sometimes','string','max:255'],
            'make' => ['sometimes','nullable','string','max:255'],
            'model' => ['sometimes','nullable','string','max:255'],
            'serial_number' => ['sometimes','nullable','string','max:255'],
            'status' => ['sometimes','in:Active,Under Maintenance,Retired'],
            'type_id' => ['sometimes','integer','exists:hr_equipment_types,id'],
            'branch_id' => ['sometimes','nullable','integer','exists:branches,id'],
            'branch_area_id' => ['nullable', 'exists:branch_areas,id'],
            'purchase_price' => ['sometimes','nullable','numeric','min:0'],
            'purchase_date' => ['sometimes','nullable','date'],
            'warranty_years' => ['sometimes','nullable','integer','min:0','max:20'],
            'warranty_months' => ['sometimes','nullable','integer','min:0','max:12'],
            'warranty_end_date' => ['sometimes','nullable','date'],
            'periodic_service' => ['sometimes','nullable','boolean'],
            'service_interval_days' => ['sometimes','nullable','integer','min:0','max:3650'],
            'last_serviced' => ['sometimes','nullable','date'],
            'next_service_date' => ['sometimes','nullable','date'],
        ];
    }
}
