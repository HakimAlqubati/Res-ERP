<?php

namespace App\Rules\HR\Applications;

use App\Models\LeaveRequest;
use App\Models\EmployeeApplicationV2;
use Carbon\Carbon;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\ValidationException;

/**
 * NoLeaveOverlapRule
 *
 * Ensures the new leave request does not overlap with any existing approved leave request
 * for the same employee.
 */
class NoLeaveOverlapRule implements ValidationRule
{
    public function __construct(
        protected int|string|null $employeeId = null,
        protected string|Carbon|null $startDate = null,
        protected string|Carbon|null $endDate = null,
        protected int|string|null $ignoreApplicationId = null,
        protected int|string|null $ignoreLeaveRequestId = null,
    ) {}

    /**
     * One-liner façade for use in Observers or Services.
     *
     *   NoLeaveOverlapRule::check($leaveRequest);
     *
     * @throws ValidationException
     */
    public static function check(LeaveRequest $leaveRequest): void
    {
        $errors = [];

        (new static(
            employeeId: $leaveRequest->employee_id,
            startDate: $leaveRequest->start_date,
            endDate: $leaveRequest->end_date,
            ignoreApplicationId: $leaveRequest->application_id,
            ignoreLeaveRequestId: $leaveRequest->id,
        ))->validate(
            attribute: 'start_date',
            value: $leaveRequest->start_date,
            fail: static function (string $message) use (&$errors): void {
                $errors[] = $message;
            },
        );

        if (! empty($errors)) {
            throw ValidationException::withMessages(['start_date' => $errors]);
        }
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! $this->employeeId || ! $this->startDate || ! $this->endDate) {
            return;
        }

        $start = Carbon::parse($this->startDate);
        $end   = Carbon::parse($this->endDate);

        if ($start->gt($end)) {
            return;
        }

        $hasOverlap = LeaveRequest::where('employee_id', $this->employeeId)
            ->where('start_date', '<=', $end->toDateString())
            ->where('end_date', '>=', $start->toDateString())
            ->when(
                $this->ignoreApplicationId,
                fn ($q) => $q->where('application_id', '!=', $this->ignoreApplicationId)
            )
            ->when(
                $this->ignoreLeaveRequestId,
                fn ($q) => $q->where('id', '!=', $this->ignoreLeaveRequestId)
            )
            ->whereDoesntHave('application', function ($query) {
                $query->where('status', EmployeeApplicationV2::STATUS_REJECTED);
            })
            ->exists();

        if ($hasOverlap) {
            $fail(__('notifications.leave_request_overlap',
                ['default' => 'An approved leave request already exists that overlaps with the selected dates.']
            ));
        }
    }
}
