<?php

declare(strict_types=1);

namespace App\Modules\HR\SalaryIncrease\Http\Requests;

use App\Models\SalaryIncreaseRequest;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSalaryIncreaseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $rules = (new StoreSalaryIncreaseRequest())->rules();
        unset($rules['employee_id']);

        return $rules;
    }
}
