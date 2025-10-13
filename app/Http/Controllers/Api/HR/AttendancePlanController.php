<?php

namespace App\Http\Controllers\Api\HR;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\HR\Attendance\AttendancePlanExecutor;

class AttendancePlanController extends Controller
{
    public function execute(Request $request, AttendancePlanExecutor $executor)
    {
        $validated = $request->validate([
            'employee_id'    => 'required|integer|exists:hr_employees,id',
            'work_period_id' => 'required|integer|exists:hr_work_periods,id',
            'from_date'      => 'required|date',
            'to_date'        => 'required|date|after_or_equal:from_date',
        ]);

        $result = $executor->executePlan(
            $validated['employee_id'],
            $validated['work_period_id'],
            $validated['from_date'],
            $validated['to_date']
        );

        return response()->json($result);
    }
}
