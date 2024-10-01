<?php

namespace App\Http\Controllers;

use App\Models\Employee;

class TestController2 extends Controller
{
    public function to_test_calculate_salary_with_attendances_deducations($empId,$date){

        return  calculateAbsentDaysAndDeductSalary($empId,$date);
    }
    public function to_test_calculate_salary($empId, $date)
    {
        return calculateMonthlySalary2($empId, $date);
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

    public function to_get_multi_employees_attendances()
    {
        $empIds = explode(',', $_GET['empIds']);
        $date = $_GET['date'];

        return employeeAttendancesByDate($empIds, $date);
    }

}
