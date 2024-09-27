<?php

namespace App\Filament\Clusters\HRAttenanceCluster\Resources\AttendnaceResource\Pages;

use App\Filament\Clusters\HRAttenanceCluster\Resources\AttendnaceResource;
use App\Models\Attendance;
use App\Models\Employee;
use App\Models\WorkPeriod;
use Carbon\Carbon;
use Filament\Resources\Pages\CreateRecord;

class CreateAttendnace extends CreateRecord
{
    protected static string $resource = AttendnaceResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {

        $data['day'] = \Carbon\Carbon::parse($data['check_date'])->format('l');
        $data['created_by'] = auth()->user()->id;
        $data['updated_by'] = auth()->user()->id;
        
        $employee = Employee::find($data['employee_id']);
        if ($employee->branch()->exists()) {
            $data['branch_id'] = $employee->branch->id;
        }
        

        // Fetch work periods and convert days JSON to arrays
        $work_periods = WorkPeriod::where('active', 1)->get()->map(function ($period) {
            $period->days = json_decode($period->days);
            return $period;
        });

        // Find matching work periods for the given day
        $matching_periods = $work_periods->filter(function ($period) use ($data) {
            return in_array($data['day'], $period->days);
        });

        // Find the nearest period based on check_type and check_time
        $nearest_period = $matching_periods->sortBy(function ($period) use ($data) {
            $check_time = Carbon::parse($data['check_time']);
            $start_time = Carbon::parse($period->start_at);
            $end_time = Carbon::parse($period->end_at);

            if ($data['check_type'] == Attendance::CHECKTYPE_CHECKIN) {
                // For "checkin", find the nearest start time
                return abs($check_time->diffInMinutes($start_time, false));
            } elseif ($data['check_type'] == Attendance::CHECKTYPE_CHECKOUT) {
                // For "checkout", find the nearest end time
                return abs($check_time->diffInMinutes($end_time, false));
            }

            // Default case, though you should handle all check types
            return PHP_INT_MAX;
        })->first();

        // // Save the attendance with the period ID
        if ($nearest_period) {
            $data['period_id'] = $nearest_period->id;
        } else {
            $data['period_id'] = 0;
        }

        // Calculate delay or early departure based on check_type
        if ($nearest_period) {
            $data['period_id'] = $nearest_period->id;
            $allowed_late_minutes = $nearest_period?->allowed_count_minutes_late;
            $check_time = Carbon::parse($data['check_time']);
            $start_time = Carbon::parse($nearest_period->start_at);
            $end_time = Carbon::parse($nearest_period->end_at);

            if ($data['check_type'] == Attendance::CHECKTYPE_CHECKIN) {
                // Calculate delay time or early arrival
                if ($check_time->gt($start_time)) {
                    $data['delay_minutes'] = $start_time->diffInMinutes($check_time);
                    $data['early_arrival_minutes'] = 0;
                    if ($allowed_late_minutes > 0) {
                        if ($data['delay_minutes'] <= $allowed_late_minutes) {
                            $data['status'] = Attendance::STATUS_ON_TIME;
                        } else {
                            $data['status'] = Attendance::STATUS_LATE_ARRIVAL;
                        }
                    } else if ($allowed_late_minutes <= 0) {
                        $data['status'] = Attendance::STATUS_LATE_ARRIVAL;
                    }
                } else {
                    $data['delay_minutes'] = 0;
                    $data['early_arrival_minutes'] = $check_time->diffInMinutes($start_time);
                    if ($data['early_arrival_minutes'] == 0) {
                        $data['status'] = Attendance::STATUS_ON_TIME;
                    } else {
                        $data['status'] = Attendance::STATUS_EARLY_ARRIVAL;
                    }
                }
                $data['late_departure_minutes'] = 0;
            } elseif ($data['check_type'] == Attendance::CHECKTYPE_CHECKOUT) {

                // Find the corresponding check-in record
                $checkin_record = Attendance::where('employee_id', $data['employee_id'])
                    ->where('period_id', $data['period_id'])
                    ->where('check_type', 'checkin')
                    ->whereDate('check_date', $data['check_date'])
                    ->first();

                if ($checkin_record) {
                    $checkin_time = Carbon::parse($checkin_record->check_time);
                    $check_time = Carbon::parse($data['check_time']);
                    $start_time = Carbon::parse($nearest_period->start_at);
                    $end_time = Carbon::parse($nearest_period->end_at);

                    // Calculate the actual duration (from checkin to checkout)
                    $actual_duration = $checkin_time->diff($check_time); // Get difference in hours and minutes
                    $hours_actual = $actual_duration->h;
                    $minutes_actual = $actual_duration->i;

                    // Calculate the supposed duration (from period start to end)
                    $supposed_duration = $start_time->diff($end_time); // Get difference in hours and minutes
                    $hours_supposed = $supposed_duration->h;
                    $minutes_supposed = $supposed_duration->i;

                    // Store both durations in a format like "hours:minutes"
                    $data['actual_duration_hourly'] = sprintf('%02d:%02d', $hours_actual, $minutes_actual);
                    $data['supposed_duration_hourly'] = sprintf('%02d:%02d', $hours_supposed, $minutes_supposed);

                }
                // Calculate late departure or early departure
                if ($check_time->gt($end_time)) {
                    $data['late_departure_minutes'] = $end_time->diffInMinutes($check_time);
                    $data['early_departure_minutes'] = 0;
                    $data['status'] = Attendance::STATUS_LATE_DEPARTURE;
                } else {
                    $data['late_departure_minutes'] = 0;
                    $data['early_departure_minutes'] = $check_time->diffInMinutes($end_time);
                    $data['status'] = Attendance::STATUS_EARLY_DEPARTURE;
                }
                $data['delay_minutes'] = 0;
            }
        } else {
            // Handle the case where no matching period is found
            $data['period_id'] = 0;
            $data['delay_minutes'] = 0;
            $data['early_arrival_minutes'] = 0;
            $data['late_departure_minutes'] = 0;
            $data['early_departure_minutes'] = 0;
        }
        // dd($nearest_period, $data);
        return $data;
    }
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
