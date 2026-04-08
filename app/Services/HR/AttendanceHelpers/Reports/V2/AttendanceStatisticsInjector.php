<?php

namespace App\Services\HR\AttendanceHelpers\Reports\V2;

use App\Models\Employee;
use App\Services\HR\AttendanceHelpers\Reports\HelperFunctions;
use Illuminate\Support\Collection;

class AttendanceStatisticsInjector
{
    private bool  $flexHoursEarlyDeparture;
    private int   $minEarlyDepartureMinutes;

    public int $totalDurationSeconds = 0;
    public int $totalActualSeconds = 0;
    public int $totalApprovedOvertimeSeconds = 0;
    public int $totalMissingSeconds = 0;
    public int $totalEarlyDepartureSeconds = 0;

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
    }

    public function addTotalDurationSeconds(int $seconds): void
    {
        $this->totalDurationSeconds += $seconds;
    }

    public function addTotalActualSeconds(int $seconds): void
    {
        $this->totalActualSeconds += $seconds;
    }

    public function accumulatePeriodStats(array $lastCo, bool $discountException): void
    {
        if (!empty($lastCo['approved_overtime'])) {
            $val = $lastCo['approved_overtime'];
            if (preg_match('/^(\d+):(\d+):(\d+)$/', $val, $mx)) {
                $this->totalApprovedOvertimeSeconds += ($mx[1] * 3600) + ($mx[2] * 60) + $mx[3];
            } else {
                $h = 0; $m = 0; $s = 0;
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
                        $helper = new HelperFunctions();
                        $reflection = new \ReflectionClass($helper);
                        $method = $reflection->getMethod('timeToHoursForLateArrival');
                        $method->setAccessible(true);
                        $actualHoursFloat = $method->invoke($helper, $lastCo['total_actual_duration_hourly']);
                        $supposedHoursFloat = $method->invoke($helper, $lastCo['supposed_duration_hourly']);
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

    public function inject(Collection $report, Employee $employee): void
    {
        $report->put('statistics', HelperFunctions::calculateAttendanceStats($report));
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
            $report->put('late_hours', (new HelperFunctions())->calculateTotalLateArrival($report));
        } else {
            $report->put('total_early_departure_minutes', [
                'total_minutes' => 0, 'formatted' => '00:00:00',
                'total_seconds' => 0, 'total_hours' => 0,
            ]);
            $report->put('late_hours', ['totalMinutes' => 0, 'totalHoursFloat' => 0]);
        }
    }

    private function secsToHMS(int $seconds): string
    {
        $h = floor($seconds / 3600);
        $m = floor(($seconds % 3600) / 60);
        $s = $seconds % 60;
        return sprintf('%02d:%02d:%02d', $h, $m, $s);
    }
}
