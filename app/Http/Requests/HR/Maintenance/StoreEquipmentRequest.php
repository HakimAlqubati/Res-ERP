<?php

namespace App\Http\Requests\HR\Maintenance;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class StoreEquipmentRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }
    public function rules()
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'type_id' => ['required', 'exists:hr_equipment_types,id'],
            'status' => ['required', 'in:Active,Under Maintenance,Retired'],
            'branch_id' => ['nullable', 'integer'],
            'branch_area_id' => ['nullable', 'exists:branch_areas,id'],
            'serial_number' => ['nullable', 'string', 'max:255', 'unique:hr_equipment,serial_number'],
            'service_interval_days' => ['nullable', 'integer', 'min:0'],
            'last_serviced' => ['nullable', 'date'],
            'next_service_date' => ['nullable', 'date'],
            'warranty_years' => ['required', 'integer', 'min:1'],
            'warranty_end_date' => ['nullable', 'date'],
            'purchase_price' => ['nullable', 'numeric'],
            'model' => ['nullable', 'string'],
            'make' => ['nullable', 'string'],
        ];
    }

    /**
     * Handle a failed validation attempt.
     *
     * @param  \Illuminate\Contracts\Validation\Validator  $validator
     * @return void
     *
     * @throws \Illuminate\Http\Exceptions\HttpResponseException
     */
    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(response()->json([
            'success' => false,
            'message' => $validator->errors()->first() ?? 'Validation failed',
            'errors'  => $validator->errors(),
        ], 422));
    }
}
