<?php

namespace App\Services\HR\AttendanceHelpers\Reports;

use App\Enums\HR\Attendance\AttendanceReportStatus;
use App\Http\Resources\CheckInAttendanceResource;
use App\Http\Resources\CheckOutAttendanceResource;
use App\Models\Attendance;
use App\Models\Employee;
use App\Services\HR\AttendanceHelpers\EmployeePeriodHistoryService;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class AttendanceFetcher
{
    protected EmployeePeriodHistoryService $periodHistoryService;
    public HelperFunctions $helperFunctions;

    public function __construct(EmployeePeriodHistoryService $periodHistoryService)
    {
        $this->periodHistoryService = $periodHistoryService;
        $this->helperFunctions = new HelperFunctions();
    }

    /**
     * جلب حضور موظف ليوم أو فترة كاملة، مع الفترات التاريخية لكل يوم.
     *
     * @param Employee $employee
     * @param Carbon $startDate
     * @param Carbon $endDate
     * @return Collection [تاريخ => [اليوم, الفترات, كل فترة => سجلات الحضور]]
     */
    public function fetchEmployeeAttendances(Employee $employee, Carbon $startDate, Carbon $endDate): Collection
    {
        // 1. استخرج الفترات لكل يوم
        $periodsByDay = $this->periodHistoryService->getEmployeePeriodsByDateRange($employee, $startDate, $endDate);

        $totalDurationHours = $periodsByDay['total_duration_hours'];
        $result             = collect();

        $leaveApplications = $this->getEmployeeLeaves($employee, $startDate, $endDate);
        // 2. لكل يوم -> لكل فترة -> جلب الحضور
        foreach ($periodsByDay['days'] as $date => $data) {
            $leaveFound = null;

            foreach ($leaveApplications as $leave) {
                if (Carbon::parse($date)->between($leave->from_date, $leave->to_date)) {
                    $leaveFound = $leave;
                    break;
                }
            }

            if ($leaveFound) {
                // أرجع JSON يمثل الإجازة مباشرة لليوم هذا

                $result->put($date, [
                    'date'          => $date,
                    'day_name'      => $data['day_name'],
                    'periods'       => [],
                    'day_status'    => AttendanceReportStatus::Leave->value, // أو استخدم Enum لو عندك
                    'leave_type'    => $leaveFound->transaction_description,
                    'leave_type_id' => $leaveFound->leave_type,
                ]);
                continue; // انتقل لليوم التالي ولا تكمل معالجة الفترات
            }

            $periods            = collect();
            $overtimeCalculator = new \App\Services\HR\AttendanceHelpers\Reports\AttendanceOvertimeCalculator();

            foreach ($data['periods'] as $period) {
                $attendanceRecords = Attendance::query()
                    ->where('employee_id', $employee->id)
                    ->whereDate('check_date', $date)
                    ->where('period_id', $period['period_id'])
                    ->accepted()
                    ->orderBy('check_time')
                    ->get();

                // تقسم الحضور
                $checkInCollection  = $attendanceRecords->where('check_type', Attendance::CHECKTYPE_CHECKIN)
                    ->sortBy('id')
                    ->values();
                $checkOutCollection = $attendanceRecords->where('check_type', Attendance::CHECKTYPE_CHECKOUT)
                    ->sortBy('id')
                    ->values();
                // تحويل إلى Resources (مصفوفة رقمية)
                $checkInResources  = CheckInAttendanceResource::collection($checkInCollection)->toArray(request());
                $checkOutResources = $checkOutCollection->map(function ($item) use ($employee, $period, $date, $overtimeCalculator) {
                    $approvedOvertime = $overtimeCalculator->calculatePeriodApprovedOvertime($employee, $period, $date);
                    return (new CheckOutAttendanceResource($item, $approvedOvertime, $date))->toArray(request());
                })->all();

                // أول سجل checkout (للـ firstcheckout)
                $firstCheckoutModel    = $checkOutCollection->first();
                $firstCheckoutResource = $firstCheckoutModel ? (new CheckOutAttendanceResource($firstCheckoutModel))->toArray(request()) : null;

                if ($firstCheckoutResource) {
                    $firstCheckoutResource['period_end_at'] = $period['end_time'];
                    // أضف أي حقول إضافية هنا
                }
                $approvedOvertime = $overtimeCalculator->calculatePeriodApprovedOvertime($employee, $period, $date);

                // آخر سجل checkout (للـ lastcheckout)
                $lastCheckoutModel    = $checkOutCollection->last();
                $lastCheckoutResource = $lastCheckoutModel ? (new CheckOutAttendanceResource($lastCheckoutModel, $approvedOvertime, $date))->toArray(request()) : null;
                if ($lastCheckoutResource) {
                    $lastCheckoutResource['period_end_at'] = $period['end_time'];
                    // أضف أي حقول إضافية هنا
                }

                $checkIn = $checkInResources;

                if ($firstCheckoutResource) {
                    $checkIn['firstcheckout']                      = $firstCheckoutResource;
                    $checkIn['firstcheckout']['approved_overtime'] = $approvedOvertime;
                }

                $checkOut = $checkOutResources;
                if ($lastCheckoutResource) {
                    $checkOut['lastcheckout']                      = $lastCheckoutResource;
                    $checkOut['lastcheckout']['approved_overtime'] = $approvedOvertime;
                }

                $groupedAttendances = [
                    'checkin'  => $checkIn,
                    'checkout' => $checkOut,
                ];

                // --- منطق الحالة ---
                $hasCheckin  = count($checkInResources) > 0;
                $hasCheckout = count($checkOutResources) > 0;
                if (Carbon::parse($date)->gt(Carbon::today())) {
                    $status = AttendanceReportStatus::Future;
                } elseif (! $hasCheckin && ! $hasCheckout) {
                    $status = AttendanceReportStatus::Absent;
                } elseif ($hasCheckin && ! $hasCheckout) {
                    $status = AttendanceReportStatus::IncompleteCheckinOnly;
                } elseif (! $hasCheckin && $hasCheckout) {
                    $status = AttendanceReportStatus::IncompleteCheckoutOnly;
                } else {
                    $status = AttendanceReportStatus::Present;
                }
                $periods->push([
                    'period_id'    => $period['period_id'],
                    'period_name'  => $period['name'],
                    'start_time'   => $period['start_time'],
                    'end_time'     => $period['end_time'],
                    'attendances'  => $groupedAttendances,
                    'final_status' => $status->value,

                ]);
            }

            // ✅ تحقق إذا كان الموظف غائباً في جميع الفترات
            $allPeriodsStatus = $periods->pluck('final_status')->all();
            $allAbsent        = count($allPeriodsStatus) > 0 && count(array_unique($allPeriodsStatus)) === 1 && $allPeriodsStatus[0] === AttendanceReportStatus::Absent->value;
            if (empty($allPeriodsStatus)) {
                $dayStatus = AttendanceReportStatus::NoPeriods->value;
            } elseif (count(array_unique($allPeriodsStatus)) === 1 && $allPeriodsStatus[0] === AttendanceReportStatus::Future->value) {
                $dayStatus = AttendanceReportStatus::Future->value;
            } elseif (count(array_unique($allPeriodsStatus)) === 1 && $allPeriodsStatus[0] === AttendanceReportStatus::Absent->value) {
                $dayStatus = AttendanceReportStatus::Absent->value;
            } elseif (count(array_unique($allPeriodsStatus)) === 1 && $allPeriodsStatus[0] === AttendanceReportStatus::Present->value) {
                $dayStatus = AttendanceReportStatus::Present->value;
            } else {
                // هنا نستخدم قيمة جزئي – يمكنك تعريفها في الـ Enum أو ترجع قيمة ثابتة هنا فقط
                $dayStatus = AttendanceReportStatus::Partial->value;
            }
            $actualSeconds = 0;
            foreach ($periods as $period) {
                $lastCheckout = $period['attendances']['checkout']['lastcheckout'] ?? null;
                if ($lastCheckout && ! empty($lastCheckout['total_actual_duration_hourly'])) {
                    list($h, $m, $s) = explode(':', $lastCheckout['total_actual_duration_hourly']);
                    $actualSeconds += ($h * 3600) + ($m * 60) + $s;
                }
            }
            $actualDuration = gmdate('H:i:s', $actualSeconds);

            $result->put($date, [
                'date'                  => $date,
                'day_name'              => $data['day_name'],
                'periods'               => $periods,
                'actual_duration_hours' => $actualDuration,
                'day_status'            => $dayStatus,
            ]);
        }
        $stats = HelperFunctions::calculateAttendanceStats($result);
        $result->put('statistics', $stats);
        $result->put('total_duration_hours', $totalDurationHours);

        // جمع total actual secods
        $totalActualSeconds = 0;
        foreach ($result as $key => $day) {
            // فقط الأيام، وليست عناصر الإحصائيات أو التجميع
            if (
                is_array($day)
                && isset($day['actual_duration_hours'])
                && preg_match('/^\d{2}:\d{2}:\d{2}$/', $day['actual_duration_hours'])
            ) {
                list($h, $m, $s) = explode(':', $day['actual_duration_hours']);
                $totalActualSeconds += ($h * 3600) + ($m * 60) + $s;
            }
        }
        $totalActualDuration = sprintf(
            '%02d:%02d:%02d',
            floor($totalActualSeconds / 3600),
            ($totalActualSeconds / 60) % 60,
            $totalActualSeconds % 60
        );
        $result->put('total_actual_duration_hours', $totalActualDuration);

        $totalApprovedOvertimeSeconds = 0;
        foreach ($result as $key => $day) {
            if (
                is_array($day)
                && isset($day['periods'])
                && $day['periods'] instanceof Collection
            ) {
                foreach ($day['periods'] as $period) {
                    $lastCheckout = $period['attendances']['checkout']['lastcheckout'] ?? null;
                    if (
                        $lastCheckout &&
                        ! empty($lastCheckout['approved_overtime'])
                    ) {
                        $value = $lastCheckout['approved_overtime'];
                        if (preg_match('/^\d{2}:\d{2}:\d{2}$/', $value)) {
                            list($h, $m, $s) = explode(':', $value);
                            $totalApprovedOvertimeSeconds += ($h * 3600) + ($m * 60) + $s;
                        } else {
                            $totalApprovedOvertimeSeconds += $this->parseHourMinuteString($value);
                        }
                    }
                }
            }
        }

        $totalApprovedOvertime = sprintf(
            '%02d:%02d:%02d',
            floor($totalApprovedOvertimeSeconds / 3600),
            ($totalApprovedOvertimeSeconds / 60) % 60,
            $totalApprovedOvertimeSeconds % 60
        );

        $result->put('total_approved_overtime', $totalApprovedOvertime);

        // New logic to calculate total missing hours
        $totalMissingHoursSeconds = 0;
        foreach ($result as $key => $day) {
            if (
                is_array($day)
                && isset($day['periods'])
                && $day['periods'] instanceof Collection
            ) {
                foreach ($day['periods'] as $period) {
                    $lastCheckout = $period['attendances']['checkout']['lastcheckout'] ?? null;
                    if ($lastCheckout && isset($lastCheckout['missing_hours']['total_minutes'])) {
                        $minutes = (int) ($lastCheckout['missing_hours']['total_minutes'] ?? 0);
                        if ($minutes > 0) {
                            $totalMissingHoursSeconds += $minutes * 60;
                        }
                    }
                }
            }
        }

        // Convert total seconds to H:i:s format if needed, or just store the total minutes
        $totalMissingHours = sprintf(
            '%02d:%02d:%02d',
            floor($totalMissingHoursSeconds / 3600),
            ($totalMissingHoursSeconds / 60) % 60,
            $totalMissingHoursSeconds % 60
        );

        // Add the total missing hours to the result collection
        $result->put('total_missing_hours', [
            'total_minutes' => $totalMissingHoursSeconds / 60,
            'formatted' => $totalMissingHours,
            'total_seconds' => round($totalMissingHoursSeconds, 2),
            'total_hours' => round($totalMissingHoursSeconds / 3600, 2),
        ]);

        $totalEarlyDepartureSeconds = 0;
        foreach ($result as $key => $day) {
            if (
                is_array($day)
                && isset($day['periods'])
                && $day['periods'] instanceof Collection
            ) {
                foreach ($day['periods'] as $period) {
                    $lastCheckout = $period['attendances']['checkout']['lastcheckout'] ?? null;
                    if ($lastCheckout && isset($lastCheckout['early_departure_minutes'])) {
                        $minutes = (int) ($lastCheckout['early_departure_minutes'] ?? 0);
                        if ($minutes > 0) {
                            $totalEarlyDepartureSeconds += $minutes * 60;
                        }
                    }
                }
            }
        }

        // Convert total seconds to H:i:s format
        $totalEarlyDeparture = sprintf(
            '%02d:%02d:%02d',
            floor($totalEarlyDepartureSeconds / 3600),
            ($totalEarlyDepartureSeconds / 60) % 60,
            $totalEarlyDepartureSeconds % 60
        );

        // Add the total early departure to the result collection
        $result->put('total_early_departure_minutes', [
            'total_minutes' => $totalEarlyDepartureSeconds / 60,
            'formatted'     => $totalEarlyDeparture,
            'total_seconds' => $totalEarlyDepartureSeconds,
            'total_hours'   => round($totalEarlyDepartureSeconds / 3600, 2),
        ]);

        $result->put('late_hours',  $this->helperFunctions->calculateTotalLateArrival($result));

        return $result;
    }

    public function getEmployeePeriodAttendnaceDetails($employeeId, $periodId, $date)
    {
        $attenance = Attendance::where('employee_id', $employeeId)
            ->accepted()
            ->where('period_id', $periodId)
            ->where('check_date', $date)
            ->select('check_time', 'check_type', 'period_id')
            ->orderBy('id', 'asc')
            // ->groupBy('period_id')
            ->get();
        return $attenance;
    }

    protected function getEmployeeLeaves($employee, $startDate, $endDate)
    {
        return $employee->approvedLeaveApplications()
            ->join('hr_leave_requests', 'hr_employee_applications.id', '=', 'hr_leave_requests.application_id')
            ->where(function ($query) use ($startDate, $endDate) {
                $query->whereBetween('hr_leave_requests.start_date', [$startDate, $endDate])
                    ->orWhereBetween('hr_leave_requests.end_date', [$startDate, $endDate]);
            })
            ->join('hr_leave_types', 'hr_leave_requests.leave_type', '=', 'hr_leave_types.id')
            ->select(
                'hr_leave_requests.start_date as from_date',
                'hr_leave_requests.end_date as to_date',
                'hr_leave_requests.leave_type',
                'hr_leave_types.name as transaction_description'
            )->get();
    }

    protected function parseHourMinuteString($str)
    {
        $hours   = 0;
        $minutes = 0;
        $seconds = 0;
        if (preg_match('/(\d+)\s*h/', $str, $m)) {
            $hours = (int) $m[1];
        }

        if (preg_match('/(\d+)\s*m/', $str, $m)) {
            $minutes = (int) $m[1];
        }

        if (preg_match('/(\d+)\s*s/', $str, $m)) {
            $seconds = (int) $m[1];
        }

        return $hours * 3600 + $minutes * 60 + $seconds;
    }
}
