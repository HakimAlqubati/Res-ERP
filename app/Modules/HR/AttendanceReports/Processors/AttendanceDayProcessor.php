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

            $approvedOvertime = $this->overtimeCalculator->calcApprovedOvertimeFromMemory($periodRecords, $workPeriod, $dayOvertimes);

            $lastCheckoutResource = null;
            if ($checkOutCol->isNotEmpty()) {
                $lastCheckoutResource = (new CheckOutAttendanceResource($checkOutCol->last(), $approvedOvertime, $dateStr, $discountException, $flatDayAttendances))->toArray(request());
                $lastCheckoutResource['period_end_at'] = $endTime;
                $lastCheckoutResource['approved_overtime'] = $approvedOvertime;

                if (!empty($lastCheckoutResource['total_actual_duration_hourly'])) {
                    [$ah, $am, $as] = explode(':', $lastCheckoutResource['total_actual_duration_hourly']);
                    $dayActualSeconds += ($ah * 3600) + ($am * 60) + $as;
                }

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

        return [
            'date' => $dateStr,
            'day_name' => $dayName,
            'branch_id' => $firstHistory?->branch_id,
            'branch_name' => $firstHistory?->branch?->name,
            'periods' => $periods,
            'actual_duration_hours' => gmdate('H:i:s', $dayActualSeconds),
            'day_status' => $this->statusResolver->resolveDayStatus($periods->pluck('final_status')->all()),
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
