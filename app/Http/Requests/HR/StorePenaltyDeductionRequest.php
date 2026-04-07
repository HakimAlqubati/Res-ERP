<?php

namespace App\Http\Requests\HR;

use App\Models\PenaltyDeduction;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePenaltyDeductionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        // Add authorization logic if required, e.g., using policies or simply return true for now
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'employee_id'       => ['required', 'exists:hr_employees,id'],
            'deduction_id'      => [
                'required',
                Rule::exists('hr_deductions', 'id')->where(function ($query) {
                    $query->where('is_penalty', true);
                })
            ],
            'date'              => ['required', 'date'],
            'month'             => ['required', 'integer', 'min:1', 'max:12'],
            'year'              => ['required', 'integer', 'min:2000'],
            'penalty_amount'    => ['required', 'numeric', 'min:0'],
            'description'       => ['nullable', 'string', 'max:500'],
            'status'            => ['nullable', 'string', 'in:' . implode(',', [
                PenaltyDeduction::STATUS_PENDING,
                PenaltyDeduction::STATUS_APPROVED,
                PenaltyDeduction::STATUS_REJECTED
            ])],
        ];
    }

    /**
     * Prepare the data for validation.
     *
     * @return void
     */
    protected function prepareForValidation()
    {
        if (empty($this->status)) {
            $this->merge([
                'status' => PenaltyDeduction::STATUS_PENDING,
            ]);
        }
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array
     */
    public function messages()
    {
        return [
            'deduction_id.exists' => 'The selected deduction is either invalid or not marked as a penalty.',
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
    protected function failedValidation(\Illuminate\Contracts\Validation\Validator $validator)
    {
        throw new \Illuminate\Http\Exceptions\HttpResponseException(
            response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
                'errors'  => $validator->errors(),
            ], 422)
        );
    }
}
