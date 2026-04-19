<?php

namespace App\Http\Requests\Api\HR\Rewards;

use Illuminate\Foundation\Http\FormRequest;

class UpdateEmployeeRewardRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'employee_id'   => 'sometimes|required|exists:hr_employees,id',
            'incentive_id'  => 'sometimes|required|exists:hr_monthly_incentives,id',
            'reward_amount' => 'sometimes|required|numeric|min:0.01',
            'reason'        => 'sometimes|required|string|max:1000',
            'date'          => 'sometimes|required|date',
        ];
    }
}
