<?php

namespace App\Http\Controllers\HR;

use App\Http\Controllers\Controller;
use App\Modules\HR\Overtime\WeeklyLeaveCalculator\WeeklyLeaveCalculator;
use Illuminate\Http\Request;

class WeeklyLeaveCalculatorController extends Controller
{
    public function index(Request $request)
    {
        $result = null;

        if ($request->has('total_month_days') || $request->has('absent_days')) {
            $totalMonthDays = (int) $request->input('total_month_days', 30);
            $absentDays = (int) $request->input('absent_days', 0);
            $isPeriodEnded = (bool) ($context['is_period_ended'] ?? true);
            $isForPayroll = (bool) ($context['is_for_payroll'] ?? true);
            $hasAutoLeave = (bool) ($context['has_auto_weekly_leave'] ?? true);
            $calculator = new WeeklyLeaveCalculator;
            $result = $calculator->calculate($totalMonthDays, $absentDays, [
                'is_period_ended' => $isPeriodEnded,
                'is_for_payroll' => $isForPayroll,
                'has_auto_weekly_leave' => $hasAutoLeave,
            ]);
        }

        return view('hr.weekly_leave_calculator', compact('result'));
    }
}
