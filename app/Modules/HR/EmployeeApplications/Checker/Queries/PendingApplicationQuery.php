<?php

namespace App\Modules\HR\EmployeeApplications\Checker\Queries;

use App\Models\EmployeeApplicationV2;
use App\Modules\HR\EmployeeApplications\Checker\DTOs\CheckerFilterDTO;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;

/**
 * Encapsulates the complex query logic for finding pending applications.
 */
class PendingApplicationQuery
{
    /**
     * Builds the Eloquent query based on provided filter criteria.
     */
    public function build(CheckerFilterDTO $filter): Builder
    {
        $startDate = Carbon::createFromDate($filter->year, $filter->month, 1)->startOfMonth()->toDateString();
        $endDate = Carbon::createFromDate($filter->year, $filter->month, 1)->endOfMonth()->toDateString();

        return EmployeeApplicationV2::query()
            ->where('status', EmployeeApplicationV2::STATUS_PENDING)
            
            // Scope-based filtering
            ->when($filter->employeeIds, fn(Builder $q) => $q->whereIn('employee_id', $filter->employeeIds))
            ->when($filter->branchId, fn(Builder $q) => $q->where('branch_id', $filter->branchId))
            
            // Date boundary filtering (Master + Specific Children)
            ->where(function (Builder $query) use ($startDate, $endDate) {
                $query->whereBetween('application_date', [$startDate, $endDate])
                    ->orWhereHas('leaveRequest', fn($q) => 
                        $q->where('start_date', '<=', $endDate)
                          ->where('end_date', '>=', $startDate)
                    )
                    ->orWhereHas('missedCheckinRequest', fn($q) => 
                        $q->whereBetween('date', [$startDate, $endDate])
                    )
                    ->orWhereHas('missedCheckoutRequest', fn($q) => 
                        $q->whereBetween('date', [$startDate, $endDate])
                    )
                    ->orWhereHas('advanceRequest', fn($q) => 
                        $q->whereBetween('date', [$startDate, $endDate])
                    )
                    ->orWhereHas('mealRequest', fn($q) => 
                        $q->whereBetween('date', [$startDate, $endDate])
                    );
            });
    }
}
