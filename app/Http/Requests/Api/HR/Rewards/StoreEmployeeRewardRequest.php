<?php

namespace App\Http\Requests\Api\HR\Rewards;

use Illuminate\Foundation\Http\FormRequest;

class StoreEmployeeRewardRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Middleware handles authorization
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'employee_id'   => 'required|exists:hr_employees,id',
            'incentive_id'  => 'required|exists:hr_monthly_incentives,id,active,1,deleted_at,NULL',
            'reward_amount' => 'required|numeric|min:0.01',
            'reason'        => 'required|string|max:1000',
            'date'          => 'required|date',
        ];
    }
}
