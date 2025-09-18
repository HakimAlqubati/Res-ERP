<?php

namespace App\Http\Requests\HR;

use Illuminate\Foundation\Http\FormRequest;  

class StoreTaskRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }
    public function rules(): array
    {
        return [
            'title'        => 'required|string|max:255',
            'description'  => 'nullable|string',
            'assigned_to'  => 'required|exists:hr_employees,id',
            'assigned_by'  => 'required|exists:users,id',
            'due_date'     => 'nullable|date',
            'branch_id'    => 'nullable|exists:branches,id',
            'steps'        => 'array',
            'steps.*.title' => 'required|string|max:255',
            'is_daily'     => 'boolean',
            // لو مهمة مجدولة تضيف حقول الجدولة هنا كما يلزم
        ];
    }
}
