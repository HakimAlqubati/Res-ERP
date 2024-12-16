<?php

namespace App\Http\Controllers;

use App\Models\Allowance;
use App\Models\Deduction;
use App\Models\Employee;
use App\Models\MonthlySalaryDeductionsDetail;
use App\Models\MonthlySalaryIncreaseDetail;
use App\Models\MonthSalary;
use Carbon\Carbon;
use niklasravnsborg\LaravelPdf\Facades\Pdf;

class TestController2 extends Controller
{
    public function to_test_calculate_salary_with_attendances_deducations($empId, $date)
    {

        return calculateAbsentDaysAndDeductSalary($empId, $date);
    }
    public function to_test_calculate_salary($empId, $date)
    {
        return calculateMonthlySalaryV2($empId, $date);
    }

    public function to_test_emplployee_attendance_time()
    {
        $time = $_GET['time'];
        $day = $_GET['day']; // Saturday Friday
        $empId = $_GET['empId']; // Saturday Friday
        $checkDate = $_GET['checkDate'];
        $checkType = $_GET['checkType'];
        $employee = Employee::find($empId);
        $workTimePeriods = attendanceEmployee($employee, $time, $day, $checkType, $checkDate);
        return $workTimePeriods;
    }

    public function to_get_employee_attendances()
    {
        $empId = $_GET['empId'];
        $startDate = $_GET['startDate'];
        $endDate = $_GET['endDate'];

        return employeeAttendances($empId, $startDate, $endDate);
    }

    public function to_get_employee_attendance_period_details()
    {
        $empId = $_GET['empId'];
        $date = $_GET['date'];
        $periodId = $_GET['periodId'];

        return getEmployeePeriodAttendnaceDetails($empId, $periodId, $date);
    }


    public function to_get_multi_employees_attendances()
    {
        $empIds = explode(',', $_GET['empIds']);
        $date = $_GET['date'];

        return employeeAttendancesByDate($empIds, $date);
    }

    public function to_test_salary_slip($empId, $sid)
    {
        //    return generateSalarySlipPdf($empId,$sid);
        $employee = Employee::find($empId);
        $branch = $employee->branch;
        $data = employeeSalarySlip($empId, $sid);
        $increaseDetails = $data->increaseDetails;
        $deducationDetails = $data->deducationDetails;
        $allowanceTypes = Allowance::where('active', 1)->pluck('name', 'id')->toArray();
        $constAllowanceTypes = MonthlySalaryIncreaseDetail::ALLOWANCE_TYPES;
        $allowanceTypes = $allowanceTypes + $constAllowanceTypes;
        $month = $data->month;
        $monthName = Carbon::parse($month)->translatedFormat('F Y');
        $allowanceTypes = array_reverse($allowanceTypes, true);
        // dd($allowanceTypes);
        $employeeAllowances = collect($increaseDetails)->map(function ($allowance) use ($allowanceTypes) {
            $typeId = $allowance['type_id'];

            return [
                'id' => $allowance['id'],
                'type_id' => $typeId,
                'allowance_name' => $allowanceTypes[$typeId] ?? 'Unknown Allowance', // Fallback if allowance type is missing
                'amount' => $allowance['amount'],
            ];
        })->toArray();

        // Calculate the total allowance amount
        $totalAllowanceAmount = collect($employeeAllowances)->sum('amount') + ($data?->details[0]['overtime_pay'] ?? 0) + ($employee?->salary ?? 0) + ($data?->details[0]['total_incentives'] ?? 0);



        $deducationTypes = Deduction::where('active', 1)
            ->select('name', 'id')->pluck('name', 'id')
            ->toArray();

        $constDeducationTypes = MonthlySalaryDeductionsDetail::DEDUCTION_TYPES;
        $allDeductionTypes = $deducationTypes + $constDeducationTypes;
        $employeeDeductions = collect($deducationDetails)->map(function ($deduction) use ($allDeductionTypes) {
            $deductionId = $deduction['deduction_id'];

            return [
                'id' => $deduction['id'],
                'deduction_id' => $deductionId,
                // 'deduction_name' => $allDeductionTypes[$deductionId] ?? 'Unknown Deduction', // Fallback if deduction type is missing
                'deduction_name' => $deduction['deduction_name'] ?? 'Unknown Deduction', // Fallback if deduction type is missing
                'deduction_amount' => $deduction['deduction_amount'],
            ];
        })->toArray();
        // dd($allDeductionTypes, $deducationDetails,$employeeDeductions);
        // dd($deducationTypes);
        // Calculate the total deduction amount
        $totalDeductionAmount = collect($employeeDeductions)->sum('deduction_amount');

        return view(
            'export.reports.hr.salaries.salary-slip',
            compact(
                'data',
                'totalAllowanceAmount',
                'totalDeductionAmount',
                'employeeAllowances',
                'employeeDeductions',
                'month',
                'monthName',
                'employee',
                'branch'
            )
        );
        return view(
            'export.reports.hr.salaries.salary-slip',
            compact(
                'data',
                'employee',
                'branch',
                'monthSalary',
                'allDeductionTypes',
                'allowanceTypes',
                'deducationDetail',
                'increaseDetails',
                'values'
            )
        );

        // $pdf = Pdf::loadView('export.reports.hr.salaries.salary-slip', [
        //     'data' => $data,
        //     'employee' => $employee,
        //     'branch' => $branch,
        //     'monthSalary' => $monthSalary,
        //     'allDeductionTypes' => $allDeductionTypes,
        //     'allowanceTypes' => $allowanceTypes,
        //     'deducationDetail' => $deducationDetail,
        //     'increaseDetails' => $increaseDetails,
        // ]);
        // return response()->streamDownload(function () use ($pdf) {
        //     $pdf->stream('abc.pdf');
        // }, "abc"   . '.pdf');
        // return $pdf->stream('document.pdf');
    }
    public function reportAbsentEmployees_old($date, $branchId)
    {
        // $date = Carbon::now()->format('Y-m-d');

        // Get all employees
        $employees = Employee::where('branch_id', $branchId)->with('periods')->get();
        $absentEmployees = [];

        // Loop through employees and check if they have attendance for the date
        foreach ($employees as $employee) {
            $attendance = $employee->attendancesByDate($date)->exists();
            if (!$attendance) {
                $absentEmployees[] = $employee;
            }
        }
        return $absentEmployees;
    }



    public function reportAbsentEmployees($date, $branchId, $currentTime)
    {
        return reportAbsentEmployees($date, $branchId, $currentTime);
    }
}
