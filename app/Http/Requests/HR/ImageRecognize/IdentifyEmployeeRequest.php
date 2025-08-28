<?php

namespace App\Http\Requests\HR\ImageRecognize;

use Illuminate\Foundation\Http\FormRequest;

class IdentifyEmployeeRequest extends FormRequest
{
    public function authorize(): bool
    {
        // عدّل حسب الصلاحيات عند الحاجة
        return true;
    }

    public function rules(): array
    {
        return [
            'image' => [
                'required','file','image',
                'mimes:jpg,jpeg,png','max:5120', // 5MB
            ],
        ];
    }
}
