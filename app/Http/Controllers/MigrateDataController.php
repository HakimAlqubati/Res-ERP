<?php

namespace App\Http\Controllers;

use Exception;
use App\Models\AdvanceRequest;
use App\Models\Attendance;
use App\Models\Employee;
use App\Models\EmployeeApplication;
use App\Models\EmployeeApplicationV2;
use App\Models\WorkPeriod;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MigrateDataController extends Controller
{
    public function get_employees_attendnaces($checkDate)
    {

        $attendances = Attendance::where('check_date', $checkDate)->get();
        return $attendances;
    }
    public function get_employees_without_attendances($checkDate)
    {

        $emps = Employee::where('branch_id', 5)
            ->whereIn('id', [104])
            ->whereHas('attendances')
            ->with('periods')
            ->select('name', 'id')
            ->with(['attendances' => function ($query) use ($checkDate) {
                $query->select('id', 'employee_id', 'check_type', 'period_id', 'check_time', DB::raw('count(*) as check_count'))
                    ->where('check_date', $checkDate)
                    ->where('check_type', Attendance::CHECKTYPE_CHECKIN)
                    ->groupBy('employee_id', 'check_type', 'period_id', 'check_time', 'id')
                    ->with(['checkoutRecord' => function ($qry) {
                        $qry->select('id', 'checkinrecord_id', 'check_time', 'status', 'total_actual_duration_hourly'); // specify any fields you need from checkoutRecord

                    }]);
            }])
            ->get();
        return $emps;
        // Loop through employees and their check-ins
        foreach ($emps as $employee) {
            $period = $employee->periods->first();

            $checkin = Attendance::create([
                'employee_id' => $employee->id,
                'period_id' => $period->id,
                'created_by' => 1,
                'day' => 'Thursday',
                'check_date' => $checkDate, // Set to the same check_date as check-in
                'check_time' => $period?->start_at, // You may want to set an appropriate time
                'check_type' => Attendance::CHECKTYPE_CHECKIN,
                'status' => Attendance::STATUS_ON_TIME,
            ]);
            Attendance::create([
                'employee_id' => $employee->id,
                'period_id' => $period->id,
                'created_by' => 1,
                'day' => 'Thursday',
                'check_date' => $checkDate, // Set to the same check_date as check-in
                'check_time' => $period?->end_at, // You may want to set an appropriate time
                'check_type' => Attendance::CHECKTYPE_CHECKOUT,
                'status' => Attendance::STATUS_ON_TIME,
                'checkinrecord_id' => $checkin->id,
                'supposed_duration_hourly' => $period?->supposed_duration,
                'actual_duration_hourly' => $period?->supposed_duration,
                'total_actual_duration_hourly' => $period?->supposed_duration,
            ]);
        }
        return $emps;
    }

    
 
 
}
