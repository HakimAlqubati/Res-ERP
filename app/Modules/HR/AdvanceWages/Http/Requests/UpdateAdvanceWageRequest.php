<?php

namespace App\Modules\HR\AdvanceWages\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\AdvanceWage;

class UpdateAdvanceWageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'amount'              => ['sometimes', 'required', 'numeric', 'min:0.01'],
            'date'                => ['sometimes', 'required', 'date'],
            'reason'              => ['sometimes', 'required', 'string', 'max:255'],
            'payment_method'      => ['sometimes', 'required', 'string', 'in:' . AdvanceWage::PAYMENT_METHOD_CASH . ',' . AdvanceWage::PAYMENT_METHOD_BANK_TRANSFER],
            'bank_account_number' => ['required_if:payment_method,' . AdvanceWage::PAYMENT_METHOD_BANK_TRANSFER, 'string', 'nullable'],
            'transaction_number'  => ['required_if:payment_method,' . AdvanceWage::PAYMENT_METHOD_BANK_TRANSFER, 'string', 'nullable'],
            'notes'               => ['nullable', 'string'],
        ];
    }
}
