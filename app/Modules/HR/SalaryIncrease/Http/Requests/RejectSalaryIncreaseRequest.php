<?php

declare(strict_types=1);

namespace App\Modules\HR\SalaryIncrease\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RejectSalaryIncreaseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'rejection_reason' => ['required', 'string', 'max:1500'],
        ];
    }
}
