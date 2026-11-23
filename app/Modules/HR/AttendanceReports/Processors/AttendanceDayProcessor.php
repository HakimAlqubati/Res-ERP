<?php

namespace App\Modules\HR\AttendanceReports\Processors;

use App\Enums\HR\Attendance\AttendanceReportStatus;
use App\Http\Resources\CheckInAttendanceResource;
use App\Http\Resources\CheckOutAttendanceResource;
use App\Models\Attendance;
use App\Modules\HR\AttendanceReports\Calculators\DurationCalculator;
use App\Modules\HR\AttendanceReports\Calculators\OvertimeCalculator;
use App\Modules\HR\AttendanceReports\Calculators\StatusResolver;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Class AttendanceDayProcessor
 * 
 * Encapsulates the core business logic required to process and calculate
 * attendance logs, shifts, delays, and overtimes for a specific employee on a single day.
 */
class AttendanceDayProcessor
{
    private DurationCalculator $durationCalculator;
    private OvertimeCalculator $overtimeCalculator;
    private StatusResolver $statusResolver;

    public function __construct(
        DurationCalculator $durationCalculator,
        OvertimeCalculator $overtimeCalculator,
        StatusResolver $statusResolver
    ) {
        $this->durationCalculator = $durationCalculator;
        $this->overtimeCalculator = $overtimeCalculator;
        $this->statusResolver = $statusResolver;
    }

    /**
     * Build the attendance breakdown for a single day.
     * 
     * Iterates over all assigned work periods, matching check-ins and check-outs, calculating
     * actual durations, delays, missing hours, and approved overtimes using in-memory data.
     * It then accumulates the day's metrics into the Statistics Injector.
     * 
     * @param string $dateStr Target date in 'Y-m-d' format.
     * @param string $dayName The localized name of the day (e.g., 'Monday').
     * @param string $dayShort The short lowercase standard name of the day (e.g., 'mon').
     * @param Collection $dayHistories The work period histories active for this specific day.
     * @param Collection $dayAttendances The check-in and check-out logs for this specific day.
     * @param Collection $dayOvertimes Pre-fetched approved overtimes for this specific day.
     * @param Collection $workPeriodMap A keyed collection mapping work period IDs to Period objects.
     * @param bool $isFuture Determine if the evaluated day is strictly in the future.
     * @param bool $isToday Determine if the evaluated day is today.
     * @param bool $discountException Determine if the employee is excluded from late attendance deductions.
     * @param AttendanceStatisticsInjector $statsInjector The stateful injector to accumulate statistics.
     * @return array An array representing the completely processed and formatted day report.
     */
    public function processDay(string $dateStr, string $dayName, string $dayShort, Collection $dayHistories, Collection $dayAttendances, Collection $dayOvertimes, Collection $workPeriodMap, bool $isFuture, bool $isToday, bool $discountException, AttendanceStatisticsInjector $statsInjector): array
    {
        $periods = collect();
        $dayActualSeconds = 0;
        $nowCarbon = Carbon::now();
        $totalDurationSeconds = 0;

        $flatDayAttendances = collect();
        foreach ($dayAttendances as $atts) {
            $flatDayAttendances = $flatDayAttendances->merge($atts);
        }

        // Pre-loop resolution of day overtimes to their respective periods to avoid double-counting
        // and prevent an earlier non-overtime shift from stealing an unassigned legacy/general overtime record.
        $periodOvertimesMap = $this->mapDayOvertimesToPeriods(
            $dayOvertimes,
            $dayHistories,
            $dayAttendances,
            $workPeriodMap
        );

        foreach ($dayHistories->values() as $history) {
            $periodId = $history->period_id;
            $workPeriod = $workPeriodMap->get($periodId);
            if (!$workPeriod) continue;

            $startTime = $history->start_time ?? $workPeriod->start_at;
            $endTime   = $history->end_time   ?? $workPeriod->end_at;
            $supposedDur = $this->durationCalculator->calcSupposedDuration($startTime, $endTime, (bool)$workPeriod->day_and_night);

            if (!empty($supposedDur)) {
                [$dh, $dm, $ds] = explode(':', $supposedDur);
                $totalDurationSeconds += ($dh * 3600) + ($dm * 60) + $ds;
            }

            $periodRecords = ($dayAttendances->get($periodId) ?? collect())->sortBy('id')->values();
            $checkInCol = $periodRecords->where('check_type', Attendance::CHECKTYPE_CHECKIN)->values();
            $checkOutCol = $periodRecords->where('check_type', Attendance::CHECKTYPE_CHECKOUT)->values();

            $periodOvertimes = $periodOvertimesMap->get($periodId) ?? collect();
            $approvedOvertime = $this->overtimeCalculator->calcApprovedOvertimeFromMemory($periodRecords, $workPeriod, $periodOvertimes);

            $lastCheckoutResource = null;
            if ($checkOutCol->isNotEmpty()) {
                $lastCheckoutResource = (new CheckOutAttendanceResource($checkOutCol->last(), $approvedOvertime, $dateStr, $discountException, $flatDayAttendances))->toArray(request());
                $lastCheckoutResource['period_end_at'] = $endTime;
                $lastCheckoutResource['approved_overtime'] = $approvedOvertime;

                if (!empty($lastCheckoutResource['total_actual_duration_hourly'])) {
                    [$ah, $am, $as] = explode(':', $lastCheckoutResource['total_actual_duration_hourly']);
                    $periodActualSeconds = ($ah * 3600) + ($am * 60) + $as;
                    $dayActualSeconds += $periodActualSeconds;
                } else {
                    $periodActualSeconds = 0;
                }
                $lastCheckoutResource['total_actual_druation_hourly_formatted'] = $this->durationCalculator->formatFloatToHMS($periodActualSeconds / 3600);
                $statsInjector->accumulatePeriodStats($lastCheckoutResource, $discountException);
            }

            $checkInResources = $checkInCol->map(fn($item) => (new CheckInAttendanceResource($item, $lastCheckoutResource))->toArray(request()))->all();
            $checkOutResources = $checkOutCol->map(fn($item) => (new CheckOutAttendanceResource($item, $approvedOvertime, $dateStr, $discountException, $flatDayAttendances))->toArray(request()))->all();

            $hasIn = count($checkInResources) > 0;
            $hasOut = count($checkOutResources) > 0;

            if (!$discountException && $hasIn && $hasOut && isset($checkInResources[0]['status']) && $checkInResources[0]['status'] === Attendance::STATUS_LATE_ARRIVAL) {
                $statsInjector->accumulateLateArrival((int) ($checkInResources[0]['delay_minutes'] ?? 0));
            }

            $checkIn = $checkInResources;
            if ($checkOutCol->isNotEmpty()) {
                $fco = (new CheckOutAttendanceResource($checkOutCol->first(), $approvedOvertime, $dateStr, $discountException, $flatDayAttendances))->toArray(request());
                $fco['period_end_at'] = $endTime;
                $fco['approved_overtime'] = $approvedOvertime;
                $checkIn['firstcheckout'] = $fco;
            }

            $checkOut = $checkOutResources;
            if ($lastCheckoutResource) {
                $checkOut['lastcheckout'] = $lastCheckoutResource;
            }

            $isNotStarted = $isToday && $nowCarbon->lt(Carbon::parse("{$dateStr} {$startTime}"));
            if ($isFuture || $isNotStarted) $st = AttendanceReportStatus::Future;
            elseif (!$hasIn && !$hasOut) $st = AttendanceReportStatus::Absent;
            elseif ($hasIn && !$hasOut) $st = AttendanceReportStatus::IncompleteCheckinOnly;
            elseif (!$hasIn && $hasOut) $st = AttendanceReportStatus::IncompleteCheckoutOnly;
            else $st = AttendanceReportStatus::Present;
             $periods->push([
                'period_id' => $periodId,
                'period_name' => $workPeriod->name,
                'start_time' => $startTime,
                'end_time' => $endTime,
                'attendances' => ['checkin' => $checkIn, 'checkout' => $checkOut],
                'final_status' => $st->value,
                'supposed_duration' => $supposedDur
            ]);
        }

        $statsInjector->addTotalDurationSeconds($totalDurationSeconds);
        $statsInjector->addTotalActualSeconds($dayActualSeconds);

        // Extract branch info from the day history record(s)
        $firstHistory = $dayHistories->first();
        $periods = $periods->sortBy('start_time')->values();
        
        $dayStatus = $this->statusResolver->resolveDayStatus($periods->pluck('final_status')->all());
        
        // إذا لم يكن لديه شيفت (الفترات فارغة)، اعتبره حاضر بناءً على القاعدة
        if ($periods->isEmpty()) {
            $dayStatus = \App\Enums\HR\Attendance\AttendanceReportStatus::NoPeriods->value;
        }

        return [
            'date' => $dateStr,
            'day_name' => $dayName,
            'branch_id' => $firstHistory?->branch_id,
            'branch_name' => $firstHistory?->branch?->name,
            'periods' => $periods,
            'actual_duration_hours' => gmdate('H:i:s', $dayActualSeconds),
            'actual_duration_hours_formatted' => $this->durationCalculator->formatFloatToHMS($dayActualSeconds / 3600),
            'day_status' => $dayStatus,
            'daily_supposed_seconds' => $totalDurationSeconds,
        ];
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

    public function buildLeaveDay(string $dateStr, string $dayName, object $leave, ?int $branchId = null): array
    {
        return [
            'date'       => $dateStr,
            'day_name'   => $dayName,
            'branch_id'  => $branchId,
            'periods'    => [],
            'day_status' => AttendanceReportStatus::Leave->value,
            'leave_type' => $leave->transaction_description,
            'leave_type_id' => $leave->leave_type,
        ];
    }

    /**
     * Pre-loop allocation of approved day overtimes to their respective periods.
     *
     * - Records with explicit `period_id` map directly to that period.
     * - Records with `period_id = null` are evaluated across ALL periods of the day in strict priority order:
     *   1. Virtual No-Shift period (`VIRTUAL_PERIOD_ID = -1`) if start_time matches its check-in or it is the only period.
     *   2. Regular period whose check-in matches `start_time` (HH:MM).
     *   3. Regular period where actual worked hours exceed supposed shift hours (`actualHours > supposedHours`).
     *   4. First regular period where the employee actually attended (`$periodRecords->isNotEmpty()`).
     *   5. Virtual No-Shift period if present.
     */
    private function mapDayOvertimesToPeriods(
        Collection $dayOvertimes,
        Collection $dayHistories,
        Collection $dayAttendances,
        Collection $workPeriodMap
    ): Collection {
        $mapped = collect();

        if ($dayOvertimes->isEmpty()) {
            return $mapped;
        }

        $unassigned = collect();

        foreach ($dayOvertimes as $ot) {
            if ($ot->period_id !== null) {
                $pid = (int) $ot->period_id;
                if (! $mapped->has($pid)) {
                    $mapped->put($pid, collect());
                }
                $mapped->get($pid)->push($ot);
            } else {
                $unassigned->push($ot);
            }
        }

        if ($unassigned->isEmpty()) {
            return $mapped;
        }

        $virtualId = VirtualPeriodInjector::VIRTUAL_PERIOD_ID;
        $hasVirtualPeriod = $dayAttendances->has($virtualId);
        $periodIds = $dayHistories->pluck('period_id')->unique()->values();

        // Pre-calculate actual and supposed hours for each period in the day for priority evaluation
        $periodMetrics = [];
        foreach ($periodIds as $pid) {
            $workPeriod = $workPeriodMap->get($pid);
            if (! $workPeriod) {
                continue;
            }
            $records = ($dayAttendances->get($pid) ?? collect())->sortBy('id')->values();
            $totalMinutes = 0;

            for ($i = 0; $i < $records->count(); $i++) {
                $current = $records[$i];
                if ($current->check_type !== Attendance::CHECKTYPE_CHECKIN) {
                    continue;
                }
                $next = $records[$i + 1] ?? null;
                if ($next && $next->check_type === Attendance::CHECKTYPE_CHECKOUT) {
                    $in = Carbon::parse("{$current->check_date} {$current->check_time}");
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

            $periodMetrics[$pid] = [
                'records' => $records,
                'actual_hours' => $actualHours,
                'supposed_hours' => $supposedHours,
                'exceeded' => $actualHours > $supposedHours,
                'attended' => $records->isNotEmpty() && $actualHours > 0,
            ];
        }

        foreach ($unassigned as $ot) {
            $targetPid = null;
            $otHm = ! empty($ot->start_time) ? substr((string) $ot->start_time, 0, 5) : null;

            // Priority 1 & 2: Match by start_time (HH:MM) against check-in records across all periods (including virtual -1)
            if ($otHm !== null) {
                foreach ($periodMetrics as $pid => $metric) {
                    $hasMatchingIn = $metric['records']
                        ->where('check_type', Attendance::CHECKTYPE_CHECKIN)
                        ->contains(fn ($a) => substr((string) $a->check_time, 0, 5) === $otHm);
                    if ($hasMatchingIn) {
                        $targetPid = $pid;
                        break;
                    }
                }
            }

            // If virtual No-Shift is the only period in the day
            if ($targetPid === null && $hasVirtualPeriod && $periodIds->count() === 1 && (int) $periodIds->first() === $virtualId) {
                $targetPid = $virtualId;
            }

            // Priority 3: Period where employee actually exceeded supposed shift hours (preferring one without assigned overtime yet)
            if ($targetPid === null) {
                foreach ($periodMetrics as $pid => $metric) {
                    if ($pid !== $virtualId && $metric['exceeded'] && (! $mapped->has($pid) || $mapped->get($pid)->isEmpty())) {
                        $targetPid = $pid;
                        break;
                    }
                }
            }
            if ($targetPid === null) {
                foreach ($periodMetrics as $pid => $metric) {
                    if ($pid !== $virtualId && $metric['exceeded']) {
                        $targetPid = $pid;
                        break;
                    }
                }
            }

            // Priority 4: First period where employee actually attended (actualHours > 0)
            if ($targetPid === null) {
                foreach ($periodMetrics as $pid => $metric) {
                    if ($pid !== $virtualId && $metric['attended']) {
                        $targetPid = $pid;
                        break;
                    }
                }
            }

            // Priority 5: Virtual No-Shift period if present
            if ($targetPid === null && $hasVirtualPeriod) {
                $targetPid = $virtualId;
            }

            if ($targetPid !== null) {
                if (! $mapped->has($targetPid)) {
                    $mapped->put($targetPid, collect());
                }
                $mapped->get($targetPid)->push($ot);
            }
        }

        return $mapped;
    }
}
