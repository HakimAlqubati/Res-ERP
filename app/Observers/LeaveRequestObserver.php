<?php

namespace App\Observers;

use App\Models\LeaveRequest;
use App\Rules\HR\Applications\MaxLeavePerMonthRule;
use Carbon\Carbon;
use Illuminate\Validation\ValidationException;

/**
 * Observer for LeaveRequest model.
 *
 * يتحقق قبل إنشاء أي طلب إجازة من:
 *   1. عدم وجود تداخل مع إجازات سابقة.
 *   2. عدم تجاوز الحد الأقصى المسموح به من الأيام شهرياً (max_days_per_month).
 *
 * رمي ValidationException في creating() يُلغي INSERT ويتراجع عن
 * أي transaction محيطة (بما فيها سجل EmployeeApplicationV2 الأب).
 */
class LeaveRequestObserver
{
    /**
     * التحقق من قواعد العمل قبل الحفظ.
     *
     * @throws ValidationException
     */
    public function creating(LeaveRequest $leaveRequest): void
    {
        $this->validateNoOverlap($leaveRequest);
        $this->validateMaxDaysPerMonth($leaveRequest);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Private validation steps
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Ensures the new leave request does not overlap with any existing one
     * for the same employee.
     *
     * @throws ValidationException
     */
    private function validateNoOverlap(LeaveRequest $leaveRequest): void
    {
        $startDate = $leaveRequest->start_date;
        $endDate   = $leaveRequest->end_date;

        if (! $startDate || ! $endDate) {
            return;
        }

        $start = Carbon::parse($startDate);
        $end   = Carbon::parse($endDate);

        $hasOverlap = LeaveRequest::where('employee_id', $leaveRequest->employee_id)
            ->where('start_date', '<=', $end->toDateString())
            ->where('end_date', '>=', $start->toDateString())
            ->when(
                $leaveRequest->application_id,
                fn ($q) => $q->where('application_id', '!=', $leaveRequest->application_id)
            )
            ->exists();

        if ($hasOverlap) {
            throw ValidationException::withMessages([
                'start_date' => __('notifications.leave_request_overlap',
                    ['default' => 'An approved leave request already exists that overlaps with the selected dates.']
                ),
            ]);
        }
    }

    /**
     * Ensures the employee does not exceed the max_days_per_month cap
     * defined on the LeaveType for any calendar month touched by this request.
     *
     * @throws ValidationException
     */
    private function validateMaxDaysPerMonth(LeaveRequest $leaveRequest): void
    {
        MaxLeavePerMonthRule::check($leaveRequest);
    }
}

