<?php

namespace App\Modules\HR\AttendanceReports\Processors;

use App\Models\Employee;
use App\Services\HR\AttendanceHelpers\Reports\HelperFunctions;
use Illuminate\Support\Collection;

/**
 * Class AttendanceStatisticsInjector
 * 
 * Manages the stateful accumulation of attendance statistics (duration, overtime, late hours, etc.)
 * across multiple periods/days, and handles injecting these aggregated metrics directly into the 
 * final report payload using predefined formulas and regex parsing rules.
 */
class AttendanceStatisticsInjector
{
    private bool  $flexHoursEarlyDeparture;
    private int   $minEarlyDepartureMinutes;

    public int $totalDurationSeconds = 0;
    public int $totalActualSeconds = 0;
    public int $totalApprovedOvertimeSeconds = 0;
    public int $totalMissingSeconds = 0;
    public int $totalEarlyDepartureSeconds = 0;
    public int $totalLateMinutes = 0;

    public function __construct()
    {
        $this->flexHoursEarlyDeparture  = (bool) setting('flix_hours_early_departure');
        $this->minEarlyDepartureMinutes = (int) (setting('early_depature_deduction_minutes', 0));
    }

    public function reset(): void
    {
        $this->totalDurationSeconds = 0;
        $this->totalActualSeconds = 0;
        $this->totalApprovedOvertimeSeconds = 0;
        $this->totalMissingSeconds = 0;
        $this->totalEarlyDepartureSeconds = 0;
        $this->totalLateMinutes = 0;
    }

    public function addTotalDurationSeconds(int $seconds): void
    {
        $this->totalDurationSeconds += $seconds;
    }

    public function subtractTotalDurationSeconds(int $seconds): void
    {
        $this->totalDurationSeconds = max(0, $this->totalDurationSeconds - $seconds);
    }

    public function addTotalActualSeconds(int $seconds): void
    {
        $this->totalActualSeconds += $seconds;
    }

    public function accumulateLateArrival(int $delayMinutes): void
    {
        $this->totalLateMinutes += $delayMinutes;
    }

    /**
     * Accumulate statistical metrics from a specific period's last checkout resource.
     * 
     * This method tracks total overtimes, missing hours, and evaluates early departure deductions 
     * based on flexible hour margins and threshold settings.
     * 
     * @param array $lastCo The transformed resource array of the final check-out.
     * @param bool $discountException Determines if the employee explicitly skips late deductions.
     * @return void
     */
    public function accumulatePeriodStats(array $lastCo, bool $discountException): void
    {
        if (!empty($lastCo['approved_overtime'])) {
            $val = $lastCo['approved_overtime'];
            if (preg_match('/^(\d+):(\d+):(\d+)$/', $val, $mx)) {
                $this->totalApprovedOvertimeSeconds += ($mx[1] * 3600) + ($mx[2] * 60) + $mx[3];
            } else {
                $h = 0;
                $m = 0;
                $s = 0;
                if (preg_match('/(\d+)\s*h/', $val, $mh)) $h = (int)$mh[1];
                if (preg_match('/(\d+)\s*m/', $val, $mm)) $m = (int)$mm[1];
                if (preg_match('/(\d+)\s*s/', $val, $ms)) $s = (int)$ms[1];
                $this->totalApprovedOvertimeSeconds += ($h * 3600) + ($m * 60) + $s;
            }
        }

        if (isset($lastCo['missing_hours']['total_minutes'])) {
            $this->totalMissingSeconds += (int)($lastCo['missing_hours']['total_minutes'] * 60);
        }

        if (!$discountException && isset($lastCo['early_departure_minutes'])) {
            $edMins = (int)$lastCo['early_departure_minutes'];
            if ($edMins >= $this->minEarlyDepartureMinutes && $edMins > 0) {
                $shouldDeduct = true;
                if ($this->flexHoursEarlyDeparture) {
                    if (isset($lastCo['total_actual_duration_hourly']) && isset($lastCo['supposed_duration_hourly'])) {
                        $actualHoursFloat = $this->timeToHoursFloat($lastCo['total_actual_duration_hourly']);
                        $supposedHoursFloat = $this->timeToHoursFloat($lastCo['supposed_duration_hourly']);
                        if ($actualHoursFloat >= ($supposedHoursFloat - (HelperFunctions::FLEXIBLE_HOURS_MARGIN_MINUTES / 60))) {
                            $shouldDeduct = false;
                        }
                    }
                }
                if ($shouldDeduct) {
                    $this->totalEarlyDepartureSeconds += $edMins * 60;
                }
            }
        }
    }

    /**
     * Inject the aggregated statistics natively into the final attendance report collection.
     * 
     * Formats all accumulated integers and standardizes the output schema securely.
     * 
     * @param Collection $report The final report collection acting securely as the output payload.
     * @param Employee $employee The targeted employee to evaluate exemption rules.
     * @return void
     */
    public function inject(Collection $report, Employee $employee): void
    {
        $stats = HelperFunctions::calculateAttendanceStats($report);
        // Restore legacy: Inject the Golden Equation weekly leave calculation
        $calculator = new \App\Modules\HR\Overtime\WeeklyLeaveCalculator\WeeklyLeaveCalculator();
        $stats['weekly_leave_calculation'] = $calculator->calculate(
            $stats['required_days'] ?? $stats['total_days'] ?? 0,
            $stats['absent'] ?? 0,
            [
                'is_period_ended'       => true,
                'is_for_payroll'        => true,
                'has_auto_weekly_leave' => (bool) $employee->has_auto_weekly_leave
            ]
        );

        $report->put('statistics', $stats);
        $report->put('total_duration_hours', round($this->totalDurationSeconds / 3600, 2));
        $report->put('total_actual_duration_hours', $this->secsToHMS($this->totalActualSeconds));
        $report->put('total_approved_overtime', $this->secsToHMS($this->totalApprovedOvertimeSeconds));

        $report->put('total_missing_hours', [
            'total_minutes' => $this->totalMissingSeconds / 60,
            'formatted'     => $this->secsToHMS($this->totalMissingSeconds),
            'total_seconds' => (float) $this->totalMissingSeconds,
            'total_hours'   => round($this->totalMissingSeconds / 3600, 2),
        ]);

        if (!$employee->discount_exception_if_attendance_late) {
            $report->put('total_early_departure_minutes', [
                'total_minutes' => $this->totalEarlyDepartureSeconds / 60,
                'formatted'     => $this->secsToHMS($this->totalEarlyDepartureSeconds),
                'total_seconds' => $this->totalEarlyDepartureSeconds,
                'total_hours'   => round($this->totalEarlyDepartureSeconds / 3600, 2),
            ]);
            $report->put('late_hours', [
                'totalMinutes'    => $this->totalLateMinutes,
                'totalHoursFloat' => round($this->totalLateMinutes / 60, 1),
            ]);
        } else {
            $report->put('total_early_departure_minutes', [
                'total_minutes' => 0,
                'formatted'     => '00:00:00',
                'total_seconds' => 0,
                'total_hours'   => 0,
            ]);
            $report->put('late_hours', ['totalMinutes' => 0, 'totalHoursFloat' => 0]);
        }
    }

    private function timeToHoursFloat(string $time): float
    {
        if (preg_match('/^\d{1,2}:\d{1,2}:\d{1,2}$/', $time)) {
            $parts = explode(':', $time);
            return $parts[0] + ($parts[1] / 60) + ($parts[2] / 3600);
        }

        if (preg_match('/(\d+)\s*h\s*(\d*)\s*m*/i', $time, $matches)) {
            $hours = isset($matches[1]) ? (int) $matches[1] : 0;
            $minutes = isset($matches[2]) ? (int) $matches[2] : 0;
            $minutes += (int) setting('early_attendance_minutes', 0);
            return $hours + ($minutes / 60);
        }

        return 0.0;
    }

    private function secsToHMS(int $seconds): string
    {
        $h = floor($seconds / 3600);
        $m = floor(($seconds % 3600) / 60);
        $s = $seconds % 60;
        return sprintf('%02d:%02d:%02d', $h, $m, $s);
    }
}
