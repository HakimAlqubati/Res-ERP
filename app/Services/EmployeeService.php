<?php 

namespace App\Services;

use App\Models\Employee;
use App\Models\EmployeeBranchLog;

class EmployeeService
{
    public function changeEmployeeBranch($employeeId, $newBranchId)
    {
        // Find the employee
        $employee = Employee::find($employeeId);

        if ($employee) {
            // Close the previous branch log
            $employee->branchLogs()->whereNull('end_at')->latest()->first()?->update([
                'end_at' => now(),  // Set the end date for the previous branch log
            ]);

            // Create a new log for the new branch
            $employee->branchLogs()->create([
                'employee_id' => $employeeId,
                'branch_id' => $newBranchId,
                'start_at' => now(),
            ]);

            // Update the employee's branch
            $employee->update([
                'branch_id' => $newBranchId,
            ]);
        }

        return true;
    }
}
