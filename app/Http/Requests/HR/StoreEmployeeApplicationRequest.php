<?php

namespace App\Http\Requests\HR;

use Illuminate\Foundation\Http\FormRequest;

class StoreEmployeeApplicationRequest extends FormRequest
{
    public function authorize(): bool
    {
        // تقدر تربطها بـ policies أو roles لاحقاً
        return true;
    }

    public function rules(): array
    {
        return [
            'employee_id'        => 'required|exists:hr_employees,id',
            'application_type_id' => 'required|integer|in:1,2,3,4',
            'application_date'   => 'required|date',
            'notes'              => 'nullable|string|max:1000',

            // 🟢 Leave Request
            'leaveRequest.detail_leave_type_id' => 'required_if:application_type_id,1|exists:hr_leave_types,id',
            'leaveRequest.detail_from_date'     => 'required_if:application_type_id,1|date',
            'leaveRequest.detail_to_date'       => 'required_if:application_type_id,1|date',
            'leaveRequest.detail_days_count'    => 'required_if:application_type_id,1|integer|min:1',

            // 🟢 Advance Request
            'advance_request.detail_advance_amount'          => 'required_if:application_type_id,3|numeric|min:1',
            'advance_request.detail_monthly_deduction_amount' => 'required_if:application_type_id,3|numeric|min:1',
            'advance_request.detail_number_of_months_of_deduction' => 'nullable|integer|min:1',
            'advance_request.detail_deduction_starts_from'   => 'nullable|date',
            'advance_request.detail_deduction_ends_at'       => 'nullable|date',

            // 🟢 Attendance Fingerprint (Check-in)
            'missed_checkin_request.date' => 'required_if:application_type_id,2|date',
            'missed_checkin_request.time' => 'required_if:application_type_id,2|date_format:H:i',

            // 🟢 Departure Fingerprint (Check-out)
            'missed_checkout_request.detail_date' => 'required_if:application_type_id,4|date',
            'missed_checkout_request.detail_time' => 'required_if:application_type_id,4|date_format:H:i',
        ];
    }

    public function messages(): array
    {
        return [
            'employee_id.required' => 'The employee field is required.',
            'employee_id.exists'   => 'The selected employee does not exist.',

            'application_type_id.required' => 'The application type is required.',
            'application_type_id.in'       => 'The selected application type is invalid.',

            'application_date.required' => 'The application date is required.',
            'application_date.date'     => 'The application date must be a valid date.',

            // Leave
            'leaveRequest.detail_leave_type_id.required_if' => 'The leave type is required when the application type is leave.',
            'leaveRequest.detail_leave_type_id.exists'      => 'The selected leave type does not exist.',
            'leaveRequest.detail_from_date.required_if'     => 'The start date is required for a leave request.',
            'leaveRequest.detail_from_date.date'            => 'The start date must be a valid date.',
            'leaveRequest.detail_to_date.required_if'       => 'The end date is required for a leave request.',
            'leaveRequest.detail_to_date.date'              => 'The end date must be a valid date.',
            'leaveRequest.detail_days_count.required_if'    => 'The number of days is required for a leave request.',
            'leaveRequest.detail_days_count.integer'        => 'The number of days must be an integer.',
            'leaveRequest.detail_days_count.min'            => 'The number of leave days must be at least 1.',

            // Advance
            'advance_request.detail_advance_amount.required_if'          => 'The advance amount is required for an advance request.',
            'advance_request.detail_advance_amount.numeric'              => 'The advance amount must be a numeric value.',
            'advance_request.detail_advance_amount.min'                  => 'The advance amount must be greater than 0.',
            'advance_request.detail_monthly_deduction_amount.required_if' => 'The monthly deduction amount is required for an advance request.',
            'advance_request.detail_monthly_deduction_amount.numeric'    => 'The monthly deduction amount must be numeric.',
            'advance_request.detail_monthly_deduction_amount.min'        => 'The monthly deduction amount must be greater than 0.',
            'advance_request.detail_number_of_months_of_deduction.integer' => 'The number of months of deduction must be an integer.',
            'advance_request.detail_number_of_months_of_deduction.min'   => 'The number of months of deduction must be at least 1.',
            'advance_request.detail_deduction_starts_from.date'          => 'The deduction start date must be a valid date.',
            'advance_request.detail_deduction_ends_at.date'              => 'The deduction end date must be a valid date.',

            // Attendance (Check-in)
            'missed_checkin_request.date.required_if'      => 'The check-in date is required for an attendance request.',
            'missed_checkin_request.date.date'             => 'The check-in date must be a valid date.',
            'missed_checkin_request.time.required_if'      => 'The check-in time is required for an attendance request.',
            'missed_checkin_request.time.date_format'      => 'The check-in time must be in the format HH:MM.',

            // Departure (Check-out)
            'missed_checkout_request.detail_date.required_if' => 'The check-out date is required for a departure request.',
            'missed_checkout_request.detail_date.date'        => 'The check-out date must be a valid date.',
            'missed_checkout_request.detail_time.required_if' => 'The check-out time is required for a departure request.',
            'missed_checkout_request.detail_time.date_format' => 'The check-out time must be in the format HH:MM.',
        ];
    }
}
