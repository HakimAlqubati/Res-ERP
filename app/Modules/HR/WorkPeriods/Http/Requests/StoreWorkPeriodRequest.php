<?php

namespace App\Modules\HR\WorkPeriods\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreWorkPeriodRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'name'        => 'required|string|max:255|unique:hr_work_periods,name',
            'branch_id'   => 'required|integer|exists:branches,id',
            'active'      => 'boolean',
            'description' => 'nullable|string',
            'start_at'    => 'required|date_format:H:i:s',
            'end_at'      => 'required|date_format:H:i:s',
        ];
    }
}
