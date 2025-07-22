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
}