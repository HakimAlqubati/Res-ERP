<?php

namespace App\Services\HR\AttendanceHelpers\Reports\V2;

use App\Enums\HR\Attendance\AttendanceReportStatus;
use App\Http\Resources\CheckInAttendanceResource;
use App\Http\Resources\CheckOutAttendanceResource;
use App\Models\Attendance;
use App\Models\WorkPeriod;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class AttendanceDayProcessor
{
    private AttendanceStatisticsInjector $statsInjector;

    public function __construct(AttendanceStatisticsInjector $statsInjector)
    {
        $this->statsInjector = $statsInjector;
    }

    public function processDay(string $dateStr, string $dayName, string $dayShort, Collection $dayHistories, Collection $dayAttendances, Collection $dayOvertimes, Collection $workPeriodMap, bool $isFuture, bool $isToday, bool $discountException): array
    {
        $periods = collect();
        $dayActualSeconds = 0;
        $nowCarbon = Carbon::now();
        $totalDurationSeconds = 0;
        
        foreach ($dayHistories->values() as $history) {
            $periodId = $history->period_id;
            $workPeriod = $workPeriodMap->get($periodId);
            if (!$workPeriod) continue;

            $startTime = $history->start_time ?? $workPeriod->start_at;
            $endTime   = $history->end_time   ?? $workPeriod->end_at;
            $supposedDur = $this->calcSupposedDuration($startTime, $endTime, (bool)$workPeriod->day_and_night);

            if (!empty($supposedDur)) {
                [$dh, $dm, $ds] = explode(':', $supposedDur);
                $totalDurationSeconds += ($dh * 3600) + ($dm * 60) + $ds;
            }

            $periodRecords = ($dayAttendances->get($periodId) ?? collect())->sortBy('id')->values();
            $checkInCol = $periodRecords->where('check_type', Attendance::CHECKTYPE_CHECKIN)->values();
            $checkOutCol = $periodRecords->where('check_type', Attendance::CHECKTYPE_CHECKOUT)->values();

            $approvedOvertime = $this->calcApprovedOvertimeFromMemory($periodRecords, $workPeriod, $dayOvertimes);

            $lastCheckoutResource = null;
            if ($checkOutCol->isNotEmpty()) {
                $lastCheckoutResource = (new CheckOutAttendanceResource($checkOutCol->last(), $approvedOvertime, $dateStr))->toArray(request());
                $lastCheckoutResource['period_end_at'] = $endTime;
                
                if (!empty($lastCheckoutResource['total_actual_duration_hourly'])) {
                    [$ah, $am, $as] = explode(':', $lastCheckoutResource['total_actual_duration_hourly']);
                    $dayActualSeconds += ($ah * 3600) + ($am * 60) + $as;
                }
                
                $this->statsInjector->accumulatePeriodStats($lastCheckoutResource, $discountException);
            }

            $checkInResources = $checkInCol->map(fn($item) => (new CheckInAttendanceResource($item, $lastCheckoutResource))->toArray(request()))->all();
            $checkOutResources = $checkOutCol->map(fn($item) => (new CheckOutAttendanceResource($item, $approvedOvertime, $dateStr))->toArray(request()))->all();

            $checkIn = $checkInResources;
            if ($checkOutCol->isNotEmpty()) {
                $fco = (new CheckOutAttendanceResource($checkOutCol->first()))->toArray(request());
                $fco['period_end_at'] = $endTime;
                $fco['approved_overtime'] = $approvedOvertime;
                $checkIn['firstcheckout'] = $fco;
            }
            
            $checkOut = $checkOutResources;
            if ($lastCheckoutResource) {
                $checkOut['lastcheckout'] = $lastCheckoutResource;
                $checkOut['lastcheckout']['approved_overtime'] = $approvedOvertime;
            }

            $hasIn = count($checkInResources) > 0;
            $hasOut = count($checkOutResources) > 0;
            $isNotStarted = $isToday && $nowCarbon->lt(Carbon::parse("{$dateStr} {$startTime}"));

            if ($isFuture || $isNotStarted) $st = AttendanceReportStatus::Future;
            elseif (!$hasIn && !$hasOut) $st = AttendanceReportStatus::Absent;
            elseif ($hasIn && !$hasOut) $st = AttendanceReportStatus::IncompleteCheckinOnly;
            elseif (!$hasIn && $hasOut) $st = AttendanceReportStatus::IncompleteCheckoutOnly;
            else $st = AttendanceReportStatus::Present;

            $periods->push([
                'period_id' => $periodId, 'period_name' => $workPeriod->name,
                'start_time' => $startTime, 'end_time' => $endTime,
                'attendances' => ['checkin' => $checkIn, 'checkout' => $checkOut],
                'final_status' => $st->value,
                'supposed_duration' => $supposedDur
            ]);
        }

        $this->statsInjector->addTotalDurationSeconds($totalDurationSeconds);
        $this->statsInjector->addTotalActualSeconds($dayActualSeconds);

        return [
            'date' => $dateStr, 'day_name' => $dayName, 'periods' => $periods,
            'actual_duration_hours' => gmdate('H:i:s', $dayActualSeconds),
            'day_status' => $this->resolveDayStatus($periods->pluck('final_status')->all()),
        ];
    }

    private function calcApprovedOvertimeFromMemory(Collection $periodAttendances, WorkPeriod $workPeriod, Collection $periodOvertimes): string
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
                if ($out->lt($in)) $out->addDay();
                
                $totalMinutes += $in->diffInMinutes($out);
                $i++;
            }
        }

        $actualHours = $totalMinutes / 60;
        $supposedHours = $this->getSupposedDurationHours($workPeriod);
        $isActualLargerThanSupposed = $actualHours > $supposedHours;
        $approvedOvertimeHours = $periodOvertimes->sum('hours');

        if ($isActualLargerThanSupposed && $approvedOvertimeHours > 0) {
            return $this->formatFloatToHMS($approvedOvertimeHours + $supposedHours);
        } elseif ($isActualLargerThanSupposed) {
            return $this->formatFloatToHMS($supposedHours);
        } else {
            return $this->formatFloatToHMS($actualHours > 0 ? $actualHours + $approvedOvertimeHours : 0);
        }
    }

    private function getSupposedDurationHours(WorkPeriod $workPeriod): float
    {
        try {
            $start = Carbon::parse($workPeriod->start_at);
            $end   = Carbon::parse($workPeriod->end_at);
            if ($end->lte($start) || (bool) $workPeriod->day_and_night) $end->addDay();
            return $start->diffInMinutes($end) / 60;
        } catch (\Exception $e) {
            return 0.0;
        }
    }

    private function formatFloatToHMS(float $hours): string
    {
        $h = floor($hours);
        $m = round(($hours - $h) * 60);
        return sprintf('%dh %dm', $h, $m);
    }

    private function calcSupposedDuration(string $startTime, string $endTime, bool $dayAndNight): string
    {
        try {
            $start = Carbon::createFromFormat('H:i:s', Carbon::parse($startTime)->format('H:i:s'));
            $end   = Carbon::createFromFormat('H:i:s', Carbon::parse($endTime)->format('H:i:s'));
            if ($dayAndNight || $end->lte($start)) $end->addDay();
            return gmdate('H:i:s', $start->diffInSeconds($end));
        } catch (\Exception $e) {
            return '00:00:00';
        }
    }

    private function resolveDayStatus(array $allPeriodsStatus): string
    {
        if (empty($allPeriodsStatus)) return AttendanceReportStatus::NoPeriods->value;
        $unique = array_unique($allPeriodsStatus);
        if (count($unique) === 1) {
            $first = $unique[0];
            if ($first === AttendanceReportStatus::Future->value)  return AttendanceReportStatus::Future->value;
            if ($first === AttendanceReportStatus::Absent->value)  return AttendanceReportStatus::Absent->value;
            if ($first === AttendanceReportStatus::Present->value) return AttendanceReportStatus::Present->value;
        }
        return AttendanceReportStatus::Partial->value;
    }

    public function buildTerminatedDay(string $dateStr, string $dayName): array
    {
        return [
            'date'       => $dateStr,
            'day_name'   => $dayName,
            'periods'    => [],
            'day_status' => AttendanceReportStatus::Terminated->value,
            'leave_type' => 'Terminated',
            'leave_type_id' => null,
        ];
    }

    public function buildLeaveDay(string $dateStr, string $dayName, object $leave): array
    {
        return [
            'date'       => $dateStr,
            'day_name'   => $dayName,
            'periods'    => [],
            'day_status' => AttendanceReportStatus::Leave->value,
            'leave_type' => $leave->transaction_description,
            'leave_type_id' => $leave->leave_type,
        ];
    }
}
