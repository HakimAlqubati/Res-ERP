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

            $calculator = new WeeklyLeaveCalculator();
            $result = $calculator->calculate($totalMonthDays, $absentDays);
        }

        return view('hr.weekly_leave_calculator', compact('result'));
    }
}
