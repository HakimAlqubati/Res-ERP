<?php

namespace App\Services\HR\v2\Attendance;

use App\Models\Attendance;
use App\Models\Employee;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

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
            // Store rejected attendance record
            $this->storeRejectedRecord($ctx, __('notifications.you_dont_have_periods_today'));

            return [
                'status' => false,
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

        // dd($ctx);
        // 3. Calculate Logic (Delays, Overtime, etc.)
        if ($ctx->checkType === Attendance::CHECKTYPE_CHECKIN) {
            $this->calculator->calculateCheckIn($ctx);
        } else {
            $this->calculator->calculateCheckOut($ctx);
        }
        // dd($ctx, $ctx->checkType);

        // 4. Persist (Save to DB)
        $record = $this->persist($ctx);
        // dd($record);
        // 5. Update Durations (Post-creation logic)
        $this->updateDurations($record);

        return [
            'status' => true,
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
        // حماية: لا يمكن البحث بدون تحديد وردية
        if (!$ctx->workPeriod || !$ctx->shiftDate) {
            return null;
        }

        return Attendance::where('employee_id', $ctx->employee->id)
            ->where('period_id', $ctx->workPeriod->id) // نفس الوردية حصراً
            ->where('check_date', $ctx->shiftDate)     // نفس التاريخ المنطقي حصراً
            ->where('check_type', Attendance::CHECKTYPE_CHECKIN)
            ->where('accepted', 1)
            ->whereDoesntHave('checkout')
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

        // 2. Actual Duration for this checkout session (Only relevant for Checkout)
        if ($record->check_type === Attendance::CHECKTYPE_CHECKOUT && $record->checkinrecord_id) {
            // Get the check-in record
            $checkInRecord = Attendance::find($record->checkinrecord_id);

            if ($checkInRecord) {
                // Calculate duration between check-in and check-out
                $checkInTime = Carbon::parse($checkInRecord->real_check_date . ' ' . $checkInRecord->check_time);
                $checkOutTime = Carbon::parse($record->real_check_date . ' ' . $record->check_time);

                $actualMinutes = $checkInTime->diffInMinutes($checkOutTime);
                // dd($actualMinutes, $checkInTime, $checkOutTime);
                // Format duration as H:i
                $h = floor($actualMinutes / 60);
                $m = $actualMinutes % 60;
                // dd($actualMinutes, $h, $m, $checkInTime, $checkOutTime);
                // dd($actualMinutes, $h, $m,$checkInTime,$checkOutTime);
                $record->actual_duration_hourly = sprintf('%02d:%02d', $h, $m);
            }
        }
        // dd($record);
        // 3. Total Actual Duration (Only relevant for Checkout)
        if ($record->check_type === Attendance::CHECKTYPE_CHECKOUT) {
            // Calculate total for the day
            $totalMinutes = 0;

            // Fetch all OTHER checkouts for this employee/period/date (excluding current record)
            $checkouts = Attendance::where('employee_id', $record->employee_id)
                ->where('period_id', $record->period_id)
                ->where('check_date', $record->check_date)
                ->where('check_type', Attendance::CHECKTYPE_CHECKOUT)
                ->where('accepted', 1)
                ->where('id', '!=', $record->id) // Exclude current record
                ->get();

            // Add durations from other checkouts
            foreach ($checkouts as $checkout) {
                if ($checkout->actual_duration_hourly) {
                    $parts = explode(':', $checkout->actual_duration_hourly);
                    if (count($parts) >= 2) {
                        $totalMinutes += ((int)$parts[0] * 60) + (int)$parts[1];
                    }
                }
            }
            // Add current record's actual duration to the total
            if ($record->actual_duration_hourly) {
                // dd($record->actual_duration_hourly);
                $parts = explode(':', $record->actual_duration_hourly);
                if (count($parts) >= 2) {
                    $totalMinutes += ((int)$parts[0] * 60) + (int)$parts[1];
                }
            }

            $h = floor($totalMinutes / 60);
            $m = $totalMinutes % 60;
            // dd($h, $m, $totalMinutes, $record);
            $record->total_actual_duration_hourly = sprintf('%02d:%02d', $h, $m);
        }

        // dd(
        //     $checkInTime,
        //     $checkOutTime,
        //     $actualMinutes,
        //     $record->actual_duration_hourly,
        //     $record
        // );
        // dd($record);
        $record->save();
    }

    /**
     * Store a rejected attendance record with accepted = 0
     */
    private function storeRejectedRecord(AttendanceContext $ctx, string $message): void
    {
        try {
            $date = $ctx->requestTime->toDateString();
            $time = $ctx->requestTime->toTimeString();
            $day = $ctx->requestTime->format('l');

            // Try to get period for this day
            $period = $ctx->employee->periods()
                ->whereJsonContains('days', $day)
                ->first();

            // If no period found for this day, use any period from employee
            if (!$period) {
                $period = $ctx->employee->periods()->first();
            }

            // If still no period, we cannot create the record
            if (!$period) {
                Log::warning('Cannot store rejected attendance - employee has no periods', [
                    'employee_id' => $ctx->employee->id,
                    'message' => $message
                ]);
                return;
            }

            Attendance::storeNotAccepted(
                $ctx->employee,
                $date,
                $time,
                $day,
                $message,
                $period->id,
                $ctx->attendanceType
            );
        } catch (\Throwable $e) {
            // If storing rejected record fails, just log it
            Log::error('Failed to store rejected attendance in handler', [
                'error' => $e->getMessage(),
                'employee_id' => $ctx->employee->id,
                'message' => $message
            ]);
        }
    }
}
