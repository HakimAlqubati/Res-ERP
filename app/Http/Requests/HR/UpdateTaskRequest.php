<?php

namespace App\Http\Requests\HR;

use Illuminate\Foundation\Http\FormRequest;
// app/Http/Requests/Task/UpdateTaskRequest.php
class UpdateTaskRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }
    public function rules(): array
    {
        return [
            'title'        => 'sometimes|string|max:255',
            'description'  => 'nullable|string',
            'assigned_to'  => 'sometimes|exists:hr_employees,id',
            'due_date'     => 'nullable|date',
            'branch_id'    => 'nullable|exists:branches,id',
        ];
    }
}
