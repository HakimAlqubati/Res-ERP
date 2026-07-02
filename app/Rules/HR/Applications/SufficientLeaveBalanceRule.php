<?php

namespace App\Rules\HR\Applications;

use App\Models\LeaveBalance;
use App\Models\LeaveRequest;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\ValidationException;

/**
 * SufficientLeaveBalanceRule
 *
 * Validates that the requested leave days do not exceed the employee's available leave balance.
 */
class SufficientLeaveBalanceRule implements ValidationRule
{
    public function __construct(
        protected LeaveRequest $leaveRequest,
        protected LeaveBalance $leaveBalance,
    ) {}

    /**
     * One-liner façade for use in Observers or Services.
     *
     *   SufficientLeaveBalanceRule::check($leaveRequest, $leaveBalance);
     *
     * @throws ValidationException
     */
    public static function check(LeaveRequest $leaveRequest, LeaveBalance $leaveBalance): void
    {
        $errors = [];

        (new static($leaveRequest, $leaveBalance))->validate(
            attribute: 'days_count',
            value: $leaveRequest->days_count,
            fail: static function (string $message) use (&$errors): void {
                $errors[] = $message;
            },
        );

        if (! empty($errors)) {
            throw ValidationException::withMessages(['days_count' => $errors]);
        }
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $days = (float) $value;
        
        // Prevent validating if no days are requested
        if ($days <= 0) {
            return;
        }

        // Calculate the actual available balance for this specific request.
        // We add back pendingToRemove because if this request is already marked as pending,
        // those days were already deducted from available_balance.
        $pendingToRemove = min($this->leaveBalance->pending_days, $days);
        $effectiveAvailable = $this->leaveBalance->available_balance + $pendingToRemove;

        if ($days > $effectiveAvailable) {
            $fail(__('notifications.insufficient_leave_balance', [
                'requested' => $days,
                'available' => $effectiveAvailable,
            ]) ?: "Insufficient leave balance. Requested: {$days}, Available: {$effectiveAvailable}");
        }
    }
}
