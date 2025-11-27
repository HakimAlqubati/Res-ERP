<?php

namespace App\Services\HR\v2\Attendance;

use App\Models\Attendance;
use App\Models\Employee;
use Carbon\Carbon;

class AttendanceHandlerV2
{
    public function __construct(
        protected ShiftResolver $shiftResolver,
        protected AttendanceCalculator $calculator
    ) {}

    public function handle(AttendanceContext $ctx): array
    {
        // 1. Resolve Shift
        $shiftInfo = $this->shiftResolver->resolve($ctx->employee, $ctx->requestTime);

        if (!$shiftInfo) {
            return [
                'success' => false,
                'message' => __('notifications.you_dont_have_periods_today'),
            ];
        }

        $ctx->setShift(
            $shiftInfo['period'],
            $shiftInfo['date'],
            $shiftInfo['day'],
            $shiftInfo['bounds']
        );

        // 2. Determine Check Type (CheckIn vs CheckOut)
        $this->determineCheckType($ctx);

        // 3. Calculate Logic (Delays, Overtime, etc.)
        if ($ctx->checkType === Attendance::CHECKTYPE_CHECKIN) {
            $this->calculator->calculateCheckIn($ctx);
        } else {
            $this->calculator->calculateCheckOut($ctx);
        }

        // 4. Persist (Save to DB)
        $record = $this->persist($ctx);

        // 5. Update Durations (Post-creation logic)
        $this->updateDurations($record);

        return [
            'success' => true,
            'message' => $ctx->checkType == Attendance::CHECKTYPE_CHECKIN
                ? __('notifications.check_in_success')
                : __('notifications.check_out_success'),
            'data'    => $record,
        ];
    }

    private function determineCheckType(AttendanceContext $ctx): void
    {
        // If type is explicitly provided in payload, use it
        if (!empty($ctx->payload['type'])) {
            $ctx->checkType = $ctx->payload['type'];
            // If checkout, we need to find the last checkin
            if ($ctx->checkType === Attendance::CHECKTYPE_CHECKOUT) {
                $ctx->lastAction = $this->findOpenCheckIn($ctx);
            }
            return;
        }

        // Auto-detect
        $lastCheckIn = $this->findOpenCheckIn($ctx);

        if ($lastCheckIn) {
            $ctx->checkType = Attendance::CHECKTYPE_CHECKOUT;
            $ctx->lastAction = $lastCheckIn;
        } else {
            $ctx->checkType = Attendance::CHECKTYPE_CHECKIN;
        }
    }

    private function findOpenCheckIn(AttendanceContext $ctx)
    {
        // Find the last check-in for this employee & period that doesn't have a checkout
        return Attendance::where('employee_id', $ctx->employee->id)
            ->where('period_id', $ctx->workPeriod->id)
            ->where('check_type', Attendance::CHECKTYPE_CHECKIN)
            ->where('accepted', 1)
            // Ensure it matches the "Shift Date" logic roughly
            // Or just take the very last one? 
            // Better to constrain by date range to avoid closing very old shifts
            ->whereBetween('check_date', [
                Carbon::parse($ctx->shiftDate)->subDay()->toDateString(),
                Carbon::parse($ctx->shiftDate)->addDay()->toDateString()
            ])
            ->whereDoesntHave('checkout') // Assuming relation exists
            ->latest('id')
            ->first();
    }

    private function persist(AttendanceContext $ctx)
    {
        $data = [
            'employee_id'     => $ctx->employee->id,
            'period_id'       => $ctx->workPeriod->id,
            'check_date'      => $ctx->shiftDate, // Logical Date
            'check_time'      => $ctx->requestTime->toTimeString(),
            'day'             => $ctx->shiftDayName,
            'check_type'      => $ctx->checkType,
            'branch_id'       => $ctx->employee->branch_id,
            'created_by'      => auth()->id() ?? 0,
            'attendance_type' => $ctx->attendanceType,
            'status'          => $ctx->status,
            'real_check_date' => $ctx->requestTime->toDateString(), // Actual Date
            'accepted'        => 1,

            // Calculated Fields
            'delay_minutes'           => $ctx->delayMinutes,
            'early_arrival_minutes'   => $ctx->earlyArrivalMinutes,
            'late_departure_minutes'  => $ctx->lateDepartureMinutes,
            'early_departure_minutes' => $ctx->earlyDepartureMinutes,
        ];

        if ($ctx->checkType === Attendance::CHECKTYPE_CHECKOUT && $ctx->lastAction) {
            $data['checkinrecord_id'] = $ctx->lastAction->id;

            // Format duration as H:i
            $h = floor($ctx->actualMinutes / 60);
            $m = $ctx->actualMinutes % 60;
            $data['actual_duration_hourly'] = sprintf('%02d:%02d', $h, $m);
        }

        return Attendance::create($data);
    }

    public function updateDurations(Attendance $record)
    {
        // 1. Supposed Duration
        // Assuming the relationship 'period' exists and is loaded or lazy-loaded
        $period = $record->period;
        if ($period) {
            // Ensure supposed_duration is in H:i format or similar
            $record->supposed_duration_hourly = $period->supposed_duration;
        }

        // 2. Total Actual Duration (Only relevant for Checkout)
        if ($record->check_type === Attendance::CHECKTYPE_CHECKOUT) {
            // Calculate total for the day
            $totalMinutes = 0;

            // Fetch all checkouts for this employee/period/date
            $checkouts = Attendance::where('employee_id', $record->employee_id)
                ->where('period_id', $record->period_id)
                ->where('check_date', $record->check_date)
                ->where('check_type', Attendance::CHECKTYPE_CHECKOUT)
                ->where('accepted', 1)
                ->get();

            foreach ($checkouts as $checkout) {
                if ($checkout->actual_duration_hourly) {
                    $parts = explode(':', $checkout->actual_duration_hourly);
                    if (count($parts) >= 2) {
                        $totalMinutes += ((int)$parts[0] * 60) + (int)$parts[1];
                    }
                }
            }

            $h = floor($totalMinutes / 60);
            $m = $totalMinutes % 60;
            $record->total_actual_duration_hourly = sprintf('%02d:%02d', $h, $m);
        }

        $record->save();
    }
}
