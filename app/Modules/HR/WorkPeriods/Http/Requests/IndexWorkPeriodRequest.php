<?php

namespace App\Modules\HR\WorkPeriods\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class IndexWorkPeriodRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'branch_id' => 'nullable|integer|exists:branches,id',
            // Add other filters here as needed to make it scalable
        ];
    }
}
