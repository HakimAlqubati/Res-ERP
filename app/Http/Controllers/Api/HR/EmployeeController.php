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
        $employees = Employee::select('id', 'name', 'avatar', 'branch_id')
            ->when(request('branch_id'), function ($query, $branchId) {
                $query->where('branch_id', $branchId);
            })
            // ->whereNotNull('avatar')
            ->active() // scopeActive من الموديل
            ->get()
            ->map(function ($emp) {
                return [
                    'employee_id' => $emp->id,
                    'name'        => $emp->name,
                    'branch_id' => $emp->branch_id,
                    'branch' => $emp?->branch?->name,
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

    public function leaveBalancesAll()
    {
        // فلاتر اختيارية من الاستعلام
        $branchId     = request('branch_id');
        $year         = request('year');        // مثال: 2025
        $month        = request('month');       // مثال: 10
        $leaveTypeId  = request('leave_type_id'); // لتقييد نوع إجازة محدد
        $isPaid       = request('is_paid');     // 1 أو 0
        $perPage      = (int) request('per_page', 50);

        $employees = Employee::query()
            ->select('id', 'name', 'avatar', 'branch_id')
            ->with(['branch:id,name'])
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->active() // إن كان لديك scopeActive على الموديل
            ->with(['leaveTypes' => function ($q) use ($year, $month, $leaveTypeId, $isPaid) {
                // تقليل الأعمدة المسترجعة من leave_types
                $q->select('hr_leave_types.id', 'hr_leave_types.name', 'hr_leave_types.type', 'hr_leave_types.is_paid');

                // تصفية حسب نوع الإجازة
                if ($leaveTypeId) {
                    $q->where('hr_leave_types.id', $leaveTypeId);
                }

                // تصفية حسب مدفوع/غير مدفوع
                if (!is_null($isPaid)) {
                    $q->where('hr_leave_types.is_paid', (int) $isPaid);
                }

                // تصفية على حقول Pivot إن وجدت: year, month
                if ($year) {
                    $q->wherePivot('year', $year);
                }
                if ($month) {
                    $q->wherePivot('month', $month);
                }

                // تأكد من جلب أعمدة الـ Pivot
                $q->withPivot('balance', 'year', 'month');
            }])
            ->paginate($perPage);

        // تنسيق الإخراج
        $data = $employees->getCollection()->map(function ($emp) {
            return [
                'employee_id' => $emp->id,
                'name'        => $emp->name,
                'branch_id'   => $emp->branch_id,
                'branch'      => $emp?->branch?->name,
                'avatar_url'  => $emp->avatar_image, // accessor لديك
                'leave_balances' => $emp->leaveTypes->map(function ($leaveType) {
                    return [
                        'leave_type_id'   => $leaveType->id,
                        'leave_type_name' => $leaveType->name,
                        'type'            => $leaveType->type,
                        'is_paid'         => (bool) $leaveType->is_paid,
                        'balance'         => $leaveType->pivot->balance,
                        'year'            => $leaveType->pivot->year,
                        'month'           => $leaveType->pivot->month,
                    ];
                })->values(),
            ];
        });

        // أعد نفس هيكل Laravel paginate القياسي مع استبدال collection بـ $data
        $result = $employees->toArray();
        $result['data'] = $data;

        return response()->json($result);
    }
}
