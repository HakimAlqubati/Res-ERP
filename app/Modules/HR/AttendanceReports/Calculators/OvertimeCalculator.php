<?php

namespace App\Modules\HR\AttendanceReports\Calculators;

use App\Models\Attendance;
use App\Models\WorkPeriod;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Class OvertimeCalculator
 * 
 * Extracts complex overtime iteration into isolated boundaries against specific interval datasets.
 */
class OvertimeCalculator
{
    private DurationCalculator $durationCalculator;

    public function __construct(DurationCalculator $durationCalculator)
    {
        $this->durationCalculator = $durationCalculator;
    }

    /**
     * Determine approved overtime dynamically utilizing in-memory attendance periods 
     * relative to database metrics and exception approvals.
     * 
     * @param Collection $periodAttendances Checked collections strictly bound to the target period.
     * @param WorkPeriod $workPeriod Explicit mapped work timeline bounds.
     * @param Collection $periodOvertimes Previously mapped approved overtime entries securely fetched.
     * @return string Formatted H:m string matching front-end expectations securely.
     */
    public function calcApprovedOvertimeFromMemory(Collection $periodAttendances, WorkPeriod $workPeriod, Collection $periodOvertimes): string
    {
        $totalMinutes = 0;
        $records = $periodAttendances->sortBy('id')->values();

        for ($i = 0; $i < $records->count(); $i++) {
            $current = $records[$i];
            if ($current->check_type !== Attendance::CHECKTYPE_CHECKIN) continue;
            
            $next = $records[$i + 1] ?? null;
            if ($next && $next->check_type === Attendance::CHECKTYPE_CHECKOUT) {
                $in  = Carbon::parse("{$current->check_date} {$current->check_time}");
                $out = Carbon::parse("{$next->check_date} {$next->check_time}");
                if ($out->lt($in)) {
                    $out->addDay();
                }
                
                $totalMinutes += $in->diffInMinutes($out);
                $i++;
            }
        }

        $actualHours = $totalMinutes / 60;
        $supposedHours = $this->durationCalculator->getSupposedDurationHours($workPeriod);
        $isActualLargerThanSupposed = $actualHours > $supposedHours;
        $approvedOvertimeHours = $periodOvertimes->sum('hours');

        if ($isActualLargerThanSupposed && $approvedOvertimeHours > 0) {
            return $this->durationCalculator->formatFloatToHMS($approvedOvertimeHours + $supposedHours);
        } elseif ($isActualLargerThanSupposed) {
            return $this->durationCalculator->formatFloatToHMS($supposedHours);
        } else {
            return $this->durationCalculator->formatFloatToHMS($actualHours > 0 ? $actualHours + $approvedOvertimeHours : 0);
        }
    }
}
