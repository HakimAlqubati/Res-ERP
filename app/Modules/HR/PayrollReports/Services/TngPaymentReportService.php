<?php

namespace App\Modules\HR\PayrollReports\Services;

use App\Models\EmployeePaymentMethod;
use App\Models\Payroll;
use Illuminate\Database\Eloquent\Builder;

class TngPaymentReportService
{
    /**
     * Apply the base query constraints for the TnG Payment Report.
     * This centralizes the query logic so it's not repeated in the UI.
     *
     * @param Builder $query
     * @return Builder
     */
    public static function applyBaseQuery(Builder $query): Builder
    {
        return $query->with(['employee.paymentMethod', 'branch', 'employee.branch'])
            ->where('status', Payroll::STATUS_APPROVED)
            ->whereHas('employee.paymentMethod', function ($q) {
                $q->where('code', EmployeePaymentMethod::CODE_EWALLET);
            });
    }
}
