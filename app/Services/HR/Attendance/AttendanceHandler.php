<?php
namespace App\Services\HR\Attendance;

use App\Models\Employee;

class AttendanceHandler
{
    protected AttendanceValidator $validator;

    public function __construct(AttendanceValidator $validator)
    {
        $this->validator = $validator;
    }

    public function handleEmployeeAttendance(Employee $employee,
        array $data): array {

        $dateTime = $data['date_time']; // e.g. "2025-07-07 14:30:00"

        $date = date('Y-m-d', strtotime($dateTime)); // "2025-07-07"
        $time = date('H:i:s', strtotime($dateTime)); // "14:30:00"

        $employeePeriods = $employee?->periods;
        if (! is_null($employee) && count($employeePeriods) > 0) {
            $day = \Carbon\Carbon::parse($date)->format('l');

            // Decode the days array for each period
            $workTimePeriods = $employee->periods->map(function ($period) {
                $period->days = json_decode($period->days); // Ensure days are decoded
                return $period;
            });

            // Filter periods by the day
            $periodsForDay = $workTimePeriods->filter(function ($period) use ($day) {
                return in_array($day, $period->days);
            });
            $closestPeriod = $this->findClosestPeriod($time, $periodsForDay);
            // Check if no periods are found for the given day
            if ($periodsForDay->isEmpty()) {
                return
                    [
                    'success' => false,
                    'message' => __('notifications.you_dont_have_periods_today') . ' (' . $day . ')',
                ];
            }
            if ($this->validator->isTimeOutOfAllowedRange($closestPeriod, $time)) {
                return [
                    'success' => false,
                    'message' => "You cannot check in/out at this time.",
                ];
            }

            if ($closestPeriod) {

                return [
                    'success'        => true,
                    'closest_period' => $closestPeriod,
                    'message'        => '',
                ];
            }
            return
                ['success' => true,
                'message'  => 'asd'];

        } elseif (! is_null($employee) && count($employeePeriods) == 0) {

            return
                [
                'success' => false,
                'message' => __('notifications.sorry_no_working_hours_have_been_added_to_you_please_contact_the_administration'),
            ];
        } else {
            return
                [
                'success' => false,
                'message' => __('notifications.there_is_no_employee_at_this_number'),
            ];
        }
    }

    protected function findClosestPeriod(string $time, $periods)
    {
        $currentTime = strtotime($time);
        $closest     = null;
        $minDiff     = null;

        foreach ($periods as $period) {
            $start = strtotime($period->start_at);
            $end   = strtotime($period->end_at);

            $diff = min(abs($currentTime - $start), abs($currentTime - $end));

            if (is_null($minDiff) || $diff < $minDiff) {
                $minDiff = $diff;
                $closest = $period;
            }
        }

        return $closest;
    }

}