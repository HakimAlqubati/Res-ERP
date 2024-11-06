<?php

namespace App\Http\Controllers;

use App\Models\Allowance;
use App\Models\Deduction;
use App\Models\Employee;
use App\Models\MonthlySalaryDeductionsDetail;
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

    public function to_test_salary_slip($empId, $yearMonth)
    {

        $data = employeeSalarySlip($empId, $yearMonth);

        $monthSalary = $data?->monthSalary;
        $employee = $data?->employee;
        $branch = $employee->branch;
        $deducationDetail = $monthSalary?->deducationDetails->where('employee_id', $empId);
        $increaseDetails = $monthSalary?->increaseDetails->where('employee_id', $empId);
        // dd($increaseDetails,$deducationDetail);
        $deducationTypes = Deduction::where('active', 1)->select('name', 'id')->pluck('name', 'id')->toArray();

        $constDeducationTypes = MonthlySalaryDeductionsDetail::DEDUCTION_TYPES;
        $allDeductionTypes = $deducationTypes + $constDeducationTypes;

        $allowanceTypes = Allowance::where('active', 1)->select('name', 'id')->pluck('name', 'id')->toArray();
        // dd($specificDeducationTypes);
        // dd($specificAllowanceTypes);
        // dd($data, $employee, $branch, $monthSalary);
        
        return view('export.reports.hr.salaries.salary-slip', compact('data', 'employee', 'branch',
            'monthSalary', 'allDeductionTypes',
            'allowanceTypes',
            'deducationDetail', 'increaseDetails'));

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
public function reportAbsentEmployees_old($date,$branchId){
    // $date = Carbon::now()->format('Y-m-d');
        
    // Get all employees
    $employees = Employee::where('branch_id',$branchId)->with('periods')->get();
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



public function reportAbsentEmployees($date,$branchId,$currentTime){
    return reportAbsentEmployees($date,$branchId,$currentTime);
}

}
