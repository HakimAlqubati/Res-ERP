<?php

namespace App\Modules\HR\EmployeeApplications\Checker\Queries;

use App\Models\EmployeeApplicationV2;
use App\Models\Payroll;
use App\Modules\HR\EmployeeApplications\Checker\DTOs\CheckerFilterDTO;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;

/**
 * Encapsulates the complex query logic for finding pending applications.
 */
class PendingApplicationQuery
{
    /**
     * Builds the main EmployeeApplicationV2 query.
     */
    public function build(CheckerFilterDTO $filter): Builder
    {
        [$startDate, $endDate] = $this->resolveDateRange($filter);

        return EmployeeApplicationV2::query()
            ->pending()
            ->when($filter->employeeIds, fn(Builder $q) => $q->whereIn('employee_id', $filter->employeeIds))
            ->when($filter->branchId, fn(Builder $q) => $q->where('branch_id', $filter->branchId))
            ->where(function (Builder $query) use ($startDate, $endDate) {
                $query->whereBetween('application_date', [$startDate, $endDate])
                    ->orWhereHas(
                        'leaveRequest',
                        fn($q) =>
                        $q->where('start_date', '<=', $endDate)->where('end_date', '>=', $startDate)
                    )
                    ->orWhereHas('missedCheckinRequest', fn($q) => $q->whereBetween('date', [$startDate, $endDate]))
                    ->orWhereHas('missedCheckoutRequest', fn($q) => $q->whereBetween('date', [$startDate, $endDate]))
                    ->orWhereHas('advanceRequest', fn($q) => $q->whereBetween('date', [$startDate, $endDate]))
                    ->orWhereHas('mealRequest', fn($q) => $q->whereBetween('date', [$startDate, $endDate]));
            });
    }

    /**
     * Query for pending Advance Wages.
     */
    public function getAdvanceWageQuery(CheckerFilterDTO $filter): Builder
    {
        return \App\Models\AdvanceWage::query()
            ->pending()
            ->where('year', $filter->year)
            ->where('month', $filter->month)
            ->when($filter->day, fn($q) => $q->whereDay('date', $filter->day))
            ->when($filter->employeeIds, fn($q) => $q->whereIn('employee_id', $filter->employeeIds))
            ->when($filter->branchId, fn($q) => $q->where('branch_id', $filter->branchId));
    }

    /**
     * Query for pending Overtime requests.
     */
    public function getOvertimeQuery(CheckerFilterDTO $filter): Builder
    {
        [$startDate, $endDate] = $this->resolveDateRange($filter);

        return \App\Models\EmployeeOvertime::query()
            ->pending()
            ->whereBetween('date', [$startDate, $endDate])
            ->when($filter->employeeIds, fn($q) => $q->whereIn('employee_id', $filter->employeeIds))
            ->when($filter->branchId, fn($q) => $q->where('branch_id', $filter->branchId));
    }

    /**
     * Query for pending Employee Rewards.
     */
    public function getEmployeeRewardQuery(CheckerFilterDTO $filter): Builder
    {
        return \App\Models\EmployeeReward::query()
            ->pending()
            ->where('year', $filter->year)
            ->where('month', $filter->month)
            ->when($filter->employeeIds, fn($q) => $q->whereIn('employee_id', $filter->employeeIds))
            ->when($filter->branchId, fn($q) => $q->where('branch_id', $filter->branchId));
    }

    /**
     * Query for pending Penalty Deductions.
     */
    public function getPenaltyDeductionQuery(CheckerFilterDTO $filter): Builder
    {
        return \App\Models\PenaltyDeduction::query()
            ->pending()
            ->where('year', $filter->year)
            ->where('month', $filter->month)
            ->when($filter->employeeIds, fn($q) => $q->whereIn('employee_id', $filter->employeeIds))
            ->when($filter->branchId, fn($q) => $q->where('branch_id', $filter->branchId));
    }

    /**
     * Query for unpaid payrolls from previous months/periods.
     */
    public function getUnpaidPreviousPayrollQuery(CheckerFilterDTO $filter): Builder
    {
        return Payroll::query()
            ->unpaid()
            ->where('status', '!=', Payroll::STATUS_CANCELLED)
            ->where(function (Builder $query) use ($filter) {
                $query->where('year', '<', $filter->year)
                    ->orWhere(function (Builder $sub) use ($filter) {
                        $sub->where('year', $filter->year)
                            ->where('month', '<', $filter->month);
                    });
            })
            ->when($filter->employeeIds, fn(Builder $q) => $q->whereIn('employee_id', $filter->employeeIds))
            ->when($filter->branchId, fn(Builder $q) => $q->where('branch_id', $filter->branchId));
    }

    /**
     * Resolves the start and end date based on whether a specific day is provided.
     * If `day` is set, returns a single-day range; otherwise returns the full month.
     */
    private function resolveDateRange(CheckerFilterDTO $filter): array
    {
        if ($filter->day) {
            $date = Carbon::createFromDate($filter->year, $filter->month, $filter->day)->toDateString();
            return [$date, $date];
        }

        $startDate = Carbon::createFromDate($filter->year, $filter->month, 1)->startOfMonth()->toDateString();
        $endDate   = Carbon::createFromDate($filter->year, $filter->month, 1)->endOfMonth()->toDateString();

        return [$startDate, $endDate];
    }
}
