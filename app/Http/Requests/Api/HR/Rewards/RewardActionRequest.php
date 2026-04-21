<?php

namespace App\Http\Requests\Api\HR\Rewards;

use Illuminate\Foundation\Http\FormRequest;

class RewardActionRequest extends FormRequest
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
        $rules = [];

        if ($this->routeIs('*.reject')) {
            $rules['reason'] = 'required|string|max:1000';
        }

        return $rules;
    }
}
