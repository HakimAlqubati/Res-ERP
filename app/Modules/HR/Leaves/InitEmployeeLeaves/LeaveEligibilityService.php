<?php

namespace App\Modules\HR\Leaves\InitEmployeeLeaves;

use App\Models\Employee;
use App\Models\LeaveType;

/**
 * Class LeaveEligibilityService
 * 
 * Handles the business logic to determine if a specific employee 
 * is eligible for a given leave type based on HR policies.
 * 
 * @package App\Modules\HR\Leaves\InitEmployeeLeaves
 */
class LeaveEligibilityService
{
    /**
     * Determine if the employee satisfies the constraints of the leave type.
     *
     * @param Employee  $employee
     * @param LeaveType $leaveType
     * @return bool
     */
    public static function isEligible(Employee $employee, LeaveType $leaveType): bool
    {
        if (!self::passesApplicabilityConstraint($employee, $leaveType)) {
            return false;
        }

        if (!self::passesBranchConstraint($employee, $leaveType)) {
            return false;
        }

        return true;
    }

    /**
     * Check if the employee matches the specific applicability category (e.g., Expats).
     *
     * @param Employee  $employee
     * @param LeaveType $leaveType
     * @return bool
     */
    private static function passesApplicabilityConstraint(Employee $employee, LeaveType $leaveType): bool
    {
        if ($leaveType->applicable_to === LeaveType::APPLICABLE_EXPAT_WITH_EP) {
            return (bool) $employee->has_employee_pass;
        }

        return true; // APPLICABLE_ALL
    }

    /**
     * Check if the leave type is available for the employee's designated branch.
     *
     * @param Employee  $employee
     * @param LeaveType $leaveType
     * @return bool
     */
    private static function passesBranchConstraint(Employee $employee, LeaveType $leaveType): bool
    {
        if ($leaveType->all_branches) {
            return true;
        }

        // Assumes 'branches' relation is eager-loaded to prevent N+1 queries.
        $allowedBranchIds = $leaveType->branches->pluck('id')->toArray();

        return in_array($employee->branch_id, $allowedBranchIds, true);
    }
}