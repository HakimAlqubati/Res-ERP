<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\Employee;
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

    public function migrateEmployeePeriodHistory_old()
    {
        DB::transaction(function () {
            // Retrieve distinct employee and period combinations with their first and last check dates
            $attendances = DB::table('hr_attendances')
                ->select(
                    'employee_id',
                    'period_id',
                    DB::raw('MIN(check_date) as start_date'),
                    DB::raw('MAX(check_date) as end_date')
                )
                ->groupBy('employee_id', 'period_id')
                ->get();
    
            foreach ($attendances as $attendance) {
                // Validate the attendance data
                if (!$attendance->employee_id || !$attendance->period_id) {
                    Log::warning('Skipping record with missing employee_id or period_id', (array) $attendance);
                    continue; // Skip if either ID is missing
                }
    
                // Check if the current period is active
                $isActivePeriod = DB::table('hr_employee_periods')
                    ->where('employee_id', $attendance->employee_id)
                    ->where('id', $attendance->period_id)
                    ->exists();
    
                // Check if the record already exists in hr_employee_period_histories
                $exists = DB::table('hr_employee_period_histories')
                    ->where('employee_id', $attendance->employee_id)
                    ->where('id', $attendance->period_id)
                    ->where('start_date', $attendance->start_date)
                    ->exists();
    
                // Insert data into hr_employee_period_histories if it doesn't exist
                if (!$exists) {
                    DB::table('hr_employee_period_histories')->insert([
                        'employee_id' => $attendance->employee_id,
                        'period_id' => $attendance->period_id,
                        'start_date' => $attendance->start_date,
                        'end_date' => $isActivePeriod ? null : $attendance->end_date, // Set end_date to null if the period is active
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                } else {
                    Log::info('Record already exists for employee_id, period_id, and start_date', (array) $attendance);
                }
            }
    
            // Now, add periods from $employee->periods that are not in attendances
            $employees = DB::table('hr_employees')->get(); // Adjust the model as necessary
            foreach ($employees as $employee) {
                $existingPeriodIds = $attendances->where('employee_id', $employee->id)->pluck('period_id')->toArray();
                $employeePeriods = DB::table('hr_employee_periods')->where('employee_id', $employee->id)->pluck('period_id')->toArray();
    
                foreach ($employeePeriods as $periodId) {
                    // Only insert if the period is not in existing attendance records or already in history
                    if (!in_array($periodId, $existingPeriodIds)) {
                        // Check if the record already exists in hr_employee_period_histories
                        $historyExists = DB::table('hr_employee_period_histories')
                            ->where('employee_id', $employee->id)
                            ->where('period_id', $periodId)
                            ->exists();
    
                        if (!$historyExists) {
                            // Add to hr_employee_period_histories with now() as start_date and null as end_date
                            DB::table('hr_employee_period_histories')->insert([
                                'employee_id' => $employee->id,
                                'period_id' => $periodId,
                                'start_date' => now(), // Use current date
                                'end_date' => null, // No end date
                                'created_at' => now(),
                                'updated_at' => now(),
                            ]);
                        }
                    }
                }
            }
        });
    }
    
    
    public function migrateEmployeePeriodHistory()
    {
        DB::transaction(function () {
            // Retrieve distinct employee and period combinations with their first and last check dates
            $attendances = DB::table('hr_attendances')
                ->select(
                    'employee_id',
                    'period_id',
                    DB::raw('MIN(check_date) as start_date'),
                    DB::raw('MAX(check_date) as end_date')
                )
                ->groupBy('employee_id', 'period_id')
                ->get();
    
            foreach ($attendances as $attendance) {
                // Validate the attendance data
                if (!$attendance->employee_id || !$attendance->period_id) {
                    Log::warning('Skipping record with missing employee_id or period_id', (array) $attendance);
                    continue; // Skip if either ID is missing
                }
    
                // Retrieve work period's start_at and end_at from 'hr_work_periods'
                $workPeriod = DB::table('hr_work_periods')
                    ->where('id', $attendance->period_id)
                    ->first(['start_at', 'end_at']);
    
                // Check if the current period is active
                $isActivePeriod = DB::table('hr_employee_periods')
                    ->where('employee_id', $attendance->employee_id)
                    ->where('period_id', $attendance->period_id)
                    ->exists();
    
                // Check if the record already exists in hr_employee_period_histories
                $exists = DB::table('hr_employee_period_histories')
                    ->where('employee_id', $attendance->employee_id)
                    ->where('period_id', $attendance->period_id)
                    ->where('start_date', $attendance->start_date)
                    ->exists();
    
                // Insert data into hr_employee_period_histories if it doesn't exist
                if (!$exists) {
                    DB::table('hr_employee_period_histories')->insert([
                        'employee_id' => $attendance->employee_id,
                        'period_id' => $attendance->period_id,
                        'start_date' => $attendance->start_date,
                        'end_date' => $isActivePeriod ? null : $attendance->end_date, // Set end_date to null if the period is active
                        'start_time' => $workPeriod ? $workPeriod->start_at : null, // Set start_at from work_periods
                        'end_time' => $workPeriod ? $workPeriod->end_at : null,   // Set end_at from work_periods
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                } else {
                    Log::info('Record already exists for employee_id, period_id, and start_date', (array) $attendance);
                }
            }
    
            // Now, add periods from $employee->periods that are not in attendances
            $employees = DB::table('hr_employees')->get(); // Adjust the model as necessary
            foreach ($employees as $employee) {
                $existingPeriodIds = $attendances->where('employee_id', $employee->id)->pluck('period_id')->toArray();
                $employeePeriods = DB::table('hr_employee_periods')->where('employee_id', $employee->id)->pluck('period_id')->toArray();
    
                foreach ($employeePeriods as $periodId) {
                    // Only insert if the period is not in existing attendance records or already in history
                    if (!in_array($periodId, $existingPeriodIds)) {
                        // Check if the record already exists in hr_employee_period_histories
                        $historyExists = DB::table('hr_employee_period_histories')
                            ->where('employee_id', $employee->id)
                            ->where('period_id', $periodId)
                            ->exists();
    
                        if (!$historyExists) {
                            // Retrieve work period's start_at and end_at from 'hr_work_periods'
                            $workPeriod = DB::table('hr_work_periods')
                                ->where('id', $periodId)
                                ->first(['start_at', 'end_at']);
    
                            // Add to hr_employee_period_histories with now() as start_date and null as end_date
                            DB::table('hr_employee_period_histories')->insert([
                                'employee_id' => $employee->id,
                                'period_id' => $periodId,
                                'start_date' => now(), // Use current date
                                'end_date' => null, // No end date
                                'start_time' => $workPeriod ? $workPeriod->start_at : null, // Set start_at from work_periods
                                'end_time' => $workPeriod ? $workPeriod->end_at : null,   // Set end_at from work_periods
                                'created_at' => now(),
                                'updated_at' => now(),
                            ]);
                        }
                    }
                }
            }
        });
    }

    public function updateAllPeriodsToDayAndNight(Request $request)
    {
        // Define your logic to determine if a period is day and night based on your requirements
        $updatedPeriods = WorkPeriod::all()->map(function ($period) {
            // Example logic: Check if period starts in the day and ends at night
            // You can customize this condition based on your business logic
            $dayAndNight = ($period->start_at > $period->end_at) ? true : false;

            // Update the period with the new field value
            $period->update([
                'day_and_night' => $dayAndNight,
            ]);

            return $period;
        });

        return response()->json([
            'message' => 'All periods updated successfully.',
            'updated_periods' => $updatedPeriods,
        ], 200);
    }
    
}
