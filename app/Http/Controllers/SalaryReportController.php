<?php
namespace App\Http\Controllers;

use App\Models\Employee;

class SalaryReportController extends Controller
{
    public function show(Employee $employee)
    {

        $month = request('month');
        $year  = request('year'); 
        
        $transactions = \App\Models\SalaryTransaction::where('employee_id', $employee->id)
            ->when($year, fn($q) => $q->where('year', $year))
            ->when($month, fn($q) => $q->where('month', $month))
            // ->orderByDesc('year')
            // ->orderByDesc('month')
            // ->orderByDesc('date')
            ->get();

        // حساب الإجمالي (صافي المستحقات)
        $total = $transactions->reduce(function ($carry, $tx) {
            return $carry + ($tx->operation === '+' ? $tx->amount : -$tx->amount);
        }, 0);

        return view('salary_report', [
            'employee'     => $employee,
            'transactions' => $transactions,
            'total'        => $total,
        ]);
    }
}