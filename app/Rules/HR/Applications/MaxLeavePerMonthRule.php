<?php

namespace App\Rules\HR\Applications;

use App\Models\LeaveBalance;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\EmployeeApplicationV2;
use Carbon\Carbon;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\ValidationException;

/**
 * MaxLeavePerMonthRule
 *
 * Prevents an employee from requesting more leave days in a single calendar
 * month than the `max_days_per_month` cap configured on the LeaveType.
 *
 * Calculation strategy
 * ────────────────────
 * When the requested period spans multiple months, the rule splits the
 * leave days per calendar month and validates each month independently.
 * This ensures e.g. a 10-day leave straddling two months is checked
 * correctly even if each month's cap would individually allow 5 days.
 *
 * The check counts:
 *   • Already-used days  (used_days on LeaveBalance for that month)
 *   • Pending days       (pending_days on LeaveBalance for that month)
 *   • Days from existing LeaveRequest rows in the same month (as a
 *     fallback when no LeaveBalance row exists yet)
 *   • The new request's days that fall in that month
 *
 * Usage in Observer / anywhere:
 *   (new MaxLeavePerMonthRule($leaveRequest))->validate('leave_type_id', $value, $fail);
 */
class MaxLeavePerMonthRule implements ValidationRule
{
    // ─────────────────────────────────────────────────────────────────────────
    // Context constants — passed to check() to pick the right error message
    // ─────────────────────────────────────────────────────────────────────────

    /** Used when the leave request is first submitted. */
    public const CONTEXT_CREATING  = 'creating';

    /** Used when an admin approves an existing leave request. */
    public const CONTEXT_APPROVING = 'approving';

    /**
     * @param  LeaveRequest  $leaveRequest         The LeaveRequest being validated (saved or unsaved).
     * @param  int|null      $excludeApplicationId  Exclude this application_id from overlap count (for updates).
     * @param  string        $context               One of the CONTEXT_* constants above.
     */
    public function __construct(
        protected LeaveRequest $leaveRequest,
        protected ?int $excludeApplicationId = null,
        protected string $context = self::CONTEXT_CREATING,
    ) {}

    // ─────────────────────────────────────────────────────────────────────────
    // One-liner façade — use this everywhere instead of calling validate() manually
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Validate the given LeaveRequest and throw a ValidationException if the
     * max_days_per_month cap is exceeded.  One line at the call-site:
     *
     *   MaxLeavePerMonthRule::check($leaveRequest);                          // on create
     *   MaxLeavePerMonthRule::check($leaveRequest, self::CONTEXT_APPROVING); // on approve
     *
     * @param  string  $context  One of the CONTEXT_* constants (default: CONTEXT_CREATING).
     * @throws ValidationException
     */
    public static function check(
        LeaveRequest $leaveRequest,
        string $context = self::CONTEXT_CREATING,
    ): void {
        $errors = [];

        (new static(
            leaveRequest:         $leaveRequest,
            excludeApplicationId: $leaveRequest->application_id,
            context:              $context,
        ))->validate(
            attribute: 'leave_type_id',
            value:     $leaveRequest->leave_type ?? $leaveRequest->leave_type_id,
            fail:      static function (string $message) use (&$errors): void {
                $errors[] = $message;
            },
        );

        if (! empty($errors)) {
            throw ValidationException::withMessages(['days_count' => $errors]);
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Core
    // ─────────────────────────────────────────────────────────────────────────

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $leaveRequest = $this->leaveRequest;

        // ── 1. Resolve the LeaveType ──────────────────────────────────────────
        // الحقل الفعلي في hr_leave_requests هو `leave_type` وليس `leave_type_id`
        $leaveTypeId = $leaveRequest->leave_type ?? $value;

        if (! $leaveTypeId) {
            return; // Nothing to validate against; let other rules handle the missing field.
        }

        /** @var LeaveType|null $leaveType */
        $leaveType = LeaveType::find($leaveTypeId);

        if (! $leaveType) {
            return;
        }

        // ── 2. Skip if no cap is configured ──────────────────────────────────
        $cap = $leaveType->max_days_per_month;

        if (is_null($cap) || $cap <= 0) {
            return;
        }

        // ── 3. Resolve request dates ──────────────────────────────────────────
        $startDate = $leaveRequest->start_date;
        $endDate   = $leaveRequest->end_date;

        if (! $startDate || ! $endDate) {
            return;
        }

        $start = Carbon::parse($startDate)->startOfDay();
        $end   = Carbon::parse($endDate)->startOfDay();

        if ($start->gt($end)) {
            return; // Invalid range; let other rules handle it.
        }

        $employeeId  = $leaveRequest->employee_id;
        $totalDays   = $leaveRequest->days_count ?? ($start->diffInDays($end) + 1);

        // ── 4. Determine calendar months affected ─────────────────────────────
        $monthlyBreakdown = $this->splitDaysByMonth($start, $end);

        // ── 5. Validate each month ────────────────────────────────────────────
        foreach ($monthlyBreakdown as [$year, $month, $newDaysInMonth]) {
            $alreadyConsumed = $this->getConsumedDaysForMonth(
                employeeId:          $employeeId,
                leaveTypeId:         $leaveTypeId,
                year:                $year,
                month:               $month,
                excludeApplicationId: $this->excludeApplicationId,
            );

            $totalAfterRequest = $alreadyConsumed + $newDaysInMonth;

            if ($totalAfterRequest > $cap) {
                $monthLabel = Carbon::create($year, $month, 1)->format('F Y');

                // Pick the message key based on context (creating vs approving).
                $msgKey = $this->context === self::CONTEXT_APPROVING
                    ? 'notifications.max_leave_per_month_exceeded_approving'
                    : 'notifications.max_leave_per_month_exceeded_creating';

                $fail(__($msgKey, [
                    'month'    => $monthLabel,
                    'cap'      => $cap,
                    'max'      => $cap,
                    'type'     => $leaveType->name,
                    'consumed' => $alreadyConsumed,
                    'new_days' => $newDaysInMonth,
                ]));
                return; // Fail-fast on the first violating month.
            }
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Helpers
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Split a date range into [year, month, daysCount] tuples per calendar month.
     *
     * @return array<int, array{int, int, int}>
     */
    protected function splitDaysByMonth(Carbon $start, Carbon $end): array
    {
        $result  = [];
        $current = $start->copy()->startOfDay();

        while ($current->lte($end)) {
            $year  = (int) $current->format('Y');
            $month = (int) $current->format('n');

            // Last day we can still count within this month:
            $endOfMonth   = $current->copy()->endOfMonth()->startOfDay();
            $chunkEnd     = $end->lt($endOfMonth) ? $end : $endOfMonth;
            $daysInMonth  = (int) $current->diffInDays($chunkEnd) + 1;

            $result[] = [$year, $month, $daysInMonth];

            // Move to the first day of the next month.
            $current = $chunkEnd->copy()->addDay()->startOfDay();
        }

        return $result;
    }

    /**
     * Returns the number of leave days already consumed (used + pending)
     * for a given employee/leave-type/year/month combination.
     *
     * Falls back to counting existing LeaveRequest rows when no LeaveBalance
     * record is found (e.g., first request of the month before balance is created).
     *
     * @param  int       $employeeId
     * @param  int       $leaveTypeId
     * @param  int       $year
     * @param  int       $month
     * @param  int|null  $excludeApplicationId  Skip this request (used for update scenarios).
     * @return float
     */
    protected function getConsumedDaysForMonth(
        int $employeeId,
        int $leaveTypeId,
        int $year,
        int $month,
        ?int $excludeApplicationId = null,
    ): float {
        // ── Primary: read from LeaveBalance ───────────────────────────────────
        $balance = LeaveBalance::where('employee_id', $employeeId)
            ->where('leave_type_id', $leaveTypeId)
            ->where('year', $year)
            ->where('month', $month)
            ->first();

        if ($balance) {
            // used_days + pending_days represent days already taken or in-flight.
            return (float) ($balance->used_days + $balance->pending_days);
        }

        // ── Fallback: count from existing LeaveRequest rows ───────────────────
        // Used when LeaveBalance doesn't exist yet for this month.
        $monthStart = Carbon::create($year, $month, 1)->startOfMonth()->toDateString();
        $monthEnd   = Carbon::create($year, $month, 1)->endOfMonth()->toDateString();

        $query = LeaveRequest::where('employee_id', $employeeId)
            ->where('leave_type', $leaveTypeId)   // الحقل الفعلي في hr_leave_requests
            ->where('start_date', '<=', $monthEnd)
            ->where('end_date', '>=', $monthStart)
            ->whereHas('application', fn ($q) =>
                $q->where('status', '!=', EmployeeApplicationV2::STATUS_REJECTED)
            );

        if ($excludeApplicationId) {
            $query->where('application_id', '!=', $excludeApplicationId);
        }

        $existingRequests = $query->get(['start_date', 'end_date', 'days_count']);

        $consumed = 0.0;

        foreach ($existingRequests as $req) {
            $reqStart = Carbon::parse($req->start_date)->startOfDay();
            $reqEnd   = Carbon::parse($req->end_date)->startOfDay();

            // Clamp to the current month.
            $overlapStart = $reqStart->lt(Carbon::parse($monthStart)) ? Carbon::parse($monthStart) : $reqStart;
            $overlapEnd   = $reqEnd->gt(Carbon::parse($monthEnd))     ? Carbon::parse($monthEnd)   : $reqEnd;

            if ($overlapStart->lte($overlapEnd)) {
                // If days_count is available, prorate it; otherwise count calendar days.
                if ($req->days_count && $reqStart->diffInDays($reqEnd) + 1 > 0) {
                    $totalReqDays  = (float) ($reqStart->diffInDays($reqEnd) + 1);
                    $overlapDays   = (float) ($overlapStart->diffInDays($overlapEnd) + 1);
                    $consumed     += ($req->days_count * ($overlapDays / $totalReqDays));
                } else {
                    $consumed += $overlapStart->diffInDays($overlapEnd) + 1;
                }
            }
        }

        return $consumed;
    }
}
