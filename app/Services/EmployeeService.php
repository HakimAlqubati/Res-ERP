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

    public function getEmployeesWithoutUser()
    {
        return Employee::whereNull('user_id')
            ->select('id', 'name', 'employee_no', 'job_title', 'branch_id', 'email')
            ->active()
            ->get()
            ->map(function ($employee) {
                if (empty($employee->email)) {
                    $parts = array_values(array_filter(explode(' ', $employee->name)));
                    $firstName = $parts[0] ?? '';
                    $secondName = $parts[1] ?? '';

                    $generatedEmail = $firstName;
                    if ($secondName) {
                        $generatedEmail .= '.' . $secondName;
                    }
                    $generatedEmail .= '@gmail.com';

                    $employee->email = strtolower($generatedEmail);
                }
                return $employee;
            });
    }

    public function createUsersForEmployees(array $data)
    {
        $createdUsers = [];
        $errors = [];

        $employeesData = $data['employees'] ?? [];

        // If no employees provided, fetch all active employees without users
        if (empty($employeesData)) {
            $employees = $this->getEmployeesWithoutUser();
            $employeesData = $employees->map(function ($employee) {
                return [
                    'id' => $employee->id,
                    'name' => $employee->name,
                    'email' => $employee->email,
                    'password' => '123456', // Default password
                ];
            })->toArray();
        }

        foreach ($employeesData as $employeeData) {
            $employee = Employee::find($employeeData['id']);

            if (!$employee) {
                $errors[] = [
                    'id' => $employeeData['id'],
                    'message' => 'Employee not found',
                ];
                continue;
            }

            if ($employee->user_id) {
                $errors[] = [
                    'id' => $employeeData['id'],
                    'message' => 'Employee already has a user',
                ];
                continue;
            }

            try {
                // Pass the specific data for this employee to the trait method
                // The trait method expects an array with keys like 'name', 'email', 'password'
                $user = $employee->createLinkedUser($employeeData);
                $createdUsers[] = [
                    'employee_id' => $employee->id,
                    'user_id' => $user->id,
                    'email' => $user->email,
                ];
            } catch (\Exception $e) {
                $errors[] = [
                    'id' => $employee->id,
                    'message' => $e->getMessage(),
                ];
            }
        }

        return [
            'created' => $createdUsers,
            'errors' => $errors,
        ];
    }
}
