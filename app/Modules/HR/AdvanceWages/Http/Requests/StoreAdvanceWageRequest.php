<?php

namespace App\Modules\HR\AdvanceWages\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\AdvanceWage;
use App\Rules\HR\Payroll\AdvanceWageLimitRule;

class StoreAdvanceWageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $date = $this->input('date', now()->toDateString());
        $year = (int) now()->setDateFrom(\Carbon\Carbon::parse($date))->year;
        $month = (int) now()->setDateFrom(\Carbon\Carbon::parse($date))->month;

        return [
            'employee_id'         => ['required', 'integer', 'exists:hr_employees,id'],
            'amount'              => [
                'required', 
                'numeric', 
                'min:0.01', 
                new AdvanceWageLimitRule($this->input('employee_id'), $year, $month)
            ],
            'date'                => ['required', 'date'],
            'reason'              => ['required', 'string', 'max:255'],
            'payment_method'      => ['required', 'string', 'in:' . AdvanceWage::PAYMENT_METHOD_CASH . ',' . AdvanceWage::PAYMENT_METHOD_BANK_TRANSFER],
            'bank_account_number' => ['required_if:payment_method,' . AdvanceWage::PAYMENT_METHOD_BANK_TRANSFER, 'string', 'nullable'],
            'transaction_number'  => ['required_if:payment_method,' . AdvanceWage::PAYMENT_METHOD_BANK_TRANSFER, 'string', 'nullable'],
            'notes'               => ['nullable', 'string'],
        ];
    }
}
