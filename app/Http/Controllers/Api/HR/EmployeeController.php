<?php

namespace App\Http\Controllers\Api\HR;

use App\Http\Controllers\Controller;
use App\Models\Employee;

class EmployeeController extends Controller
{
    /**
     * Get simple list of employees (id, name, avatar)
     */
    public function simpleList()
    {
        // يمكنك تصفية الموظفين الفعالين فقط حسب حاجتك
        $employees = Employee::select('id', 'name', 'avatar')
            ->whereNotNull('avatar')
            ->active() // scopeActive من الموديل
            ->get()
            ->map(function ($emp) {
                return [
                    'employee_id' => $emp->id,
                    'name'        => $emp->name,
                    'avatar_url'  => $emp->avatar_image, // accessor الموجود عندك getAvatarImageAttribute
                ];
            });

        return response()->json($employees);
    }

    public function leaveBalances($id)
    {
        $employee = Employee::with('leaveTypes')->findOrFail($id);

        return response()->json([
            'employee' => [
                'id'   => $employee->id,
                'name' => $employee->name,
                'leave_balances' => $employee->leaveTypes->map(function ($leaveType) {
                    return [
                        'leave_type_id'   => $leaveType->id,
                        'leave_type_name' => $leaveType->name,
                        'balance'         => $leaveType->pivot->balance,
                        'year'            => $leaveType->pivot->year,
                        'month'           => $leaveType->pivot->month,
                        'type'            => $leaveType->type,
                        'is_paid'         => $leaveType->is_paid,
                    ];
                }),
            ]
        ]);
    }
}
