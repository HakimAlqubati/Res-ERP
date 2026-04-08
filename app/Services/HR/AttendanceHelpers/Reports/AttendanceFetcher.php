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

        // 1. [التوسيع] حفظ النطاق الأصلي والعودة لبداية الشهر لضمان الرصيد
        $originalStartDate = $startDate->copy();
        $isPreviousMonth = $endDate->format('Y-m') < now()->format('Y-m');

        if ($isPreviousMonth && $startDate->format('d') !== '01') {
            $startDate = $endDate->copy()->startOfMonth();
        }

        // 1. استخرج الفترات لكل يوم
        $periodsByDay = $this->periodHistoryService->getEmployeePeriodsByDateRange($employee, $startDate, $endDate);

        $totalDurationHours = $periodsByDay['total_duration_hours'];
        $result             = collect();

        $leaveApplications = $this->getEmployeeLeaves($employee, $startDate, $endDate);

        $termination = $employee->serviceTermination()->where('status', \App\Models\EmployeeServiceTermination::STATUS_APPROVED)->first();

        // 2. لكل يوم -> لكل فترة -> جلب الحضور
        foreach ($periodsByDay['days'] as $date => $data) {
            if ($termination && \Carbon\Carbon::parse($date)->gt($termination->termination_date)) {
                $result->put($date, [
                    'date'          => $date,
                    'day_name'      => $data['day_name'],
                    'periods'       => collect(),
                    'day_status'    => AttendanceReportStatus::Terminated->value,
                    'leave_type'    => 'Terminated',
                    'leave_type_id' => null,
                ]);
                continue;
            }

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
                $approvedOvertime = $overtimeCalculator->calculatePeriodApprovedOvertime($employee, $period, $date);

                // 1. Process CheckOuts first to get duration info
                $checkOutResources = $checkOutCollection->map(function ($item) use ($employee, $period, $date, $overtimeCalculator, $approvedOvertime) {
                    return (new CheckOutAttendanceResource($item, $approvedOvertime, $date))->toArray(request());
                })->all();

                // Get last checkout resource for duration info
                $lastCheckoutModel    = $checkOutCollection->last();
                $lastCheckoutResource = $lastCheckoutModel ? (new CheckOutAttendanceResource($lastCheckoutModel, $approvedOvertime, $date))->toArray(request()) : null;

                if ($lastCheckoutResource) {
                    $lastCheckoutResource['period_end_at'] = $period['end_time'];
                }

                // 2. Process CheckIns with duration info from last checkout
                $checkInResources = $checkInCollection->map(function ($item) use ($lastCheckoutResource) {
                    return (new CheckInAttendanceResource($item, $lastCheckoutResource))->toArray(request());
                })->all();

                $checkIn = $checkInResources;

                // For legacy or specific UI needs, still attach firstcheckout/lastcheckout if needed
                $firstCheckoutModel    = $checkOutCollection->first();
                $firstCheckoutResource = $firstCheckoutModel ? (new CheckOutAttendanceResource($firstCheckoutModel))->toArray(request()) : null;

                if ($firstCheckoutResource) {
                    $firstCheckoutResource['period_end_at'] = $period['end_time'];
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

                // المستقبل: إما اليوم لاحق، أو اليوم ذاته لكن وقت الشيفت لم يبدأ بعد
                $isFutureDate  = Carbon::parse($date)->gt(Carbon::today());
                $isShiftNotYetStarted = Carbon::parse($date)->isToday()
                    && Carbon::now()->lt(Carbon::parse("{$date} {$period['start_time']}"));

                if ($isFutureDate || $isShiftNotYetStarted) {
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
 
      

        // 2. حساب الإحصائيات (للشهر كاملاً) لغرض المحاسب المالي (WeeklyLeaveCalculator) فقط
        $fullMonthStats = HelperFunctions::calculateAttendanceStats($result);
        $totalMonthDays = $fullMonthStats['required_days'] ?? $fullMonthStats['total_days'] ?? 0;
        $absentDays     = $fullMonthStats['absent'] ?? 0;

        // 3. الاحتساب المالي — يعمل بناءً على الشهر كاملاً ليعطي الخصم/الإضافي الحقيقي
        $payrollCalculation = (new \App\Modules\HR\Overtime\WeeklyLeaveCalculator\WeeklyLeaveCalculator())
            ->calculate($totalMonthDays, $absentDays, [
                'is_period_ended'       => $isPreviousMonth,
                'is_for_payroll'        => true,
                'has_auto_weekly_leave' => $employee->has_auto_weekly_leave,
            ]);

        if ($isPreviousMonth && $employee->has_auto_weekly_leave) {
            $result = $this->applyWeeklyLeaveToAbsences($result);
        }


        // 4. [القص] العودة للفلتر الأصلي الذي اختاره المستخدم في الواجهة
        if ($isPreviousMonth && $originalStartDate->gt($startDate)) {
            $result = $result->filter(function ($value, $key) use ($originalStartDate) {
                // نحتفظ فقط بالتواريخ التي تقع ضمن الفلتر الأصلي
                if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $key)) {
                    return \Carbon\Carbon::parse($key)->gte($originalStartDate);
                }
                return true; // الاحتفاظ بأي مفاتيح أخرى
            });

            // 5. إعادة ضبط إجمالي "الساعات المفترضة" لكي لا تظهر ساعات شهر كامل في تقرير 5 أيام
            $totalDurationHours = 0;
            foreach ($periodsByDay['days'] as $date => $data) {
                if (\Carbon\Carbon::parse($date)->gte($originalStartDate)) {
                    foreach ($data['periods'] as $period) {
                        $start = \Carbon\Carbon::parse($period['start_time']);
                        $end = \Carbon\Carbon::parse($period['end_time']);
                        if ($end->lessThan($start)) {
                            $end->addDay(); // معالجة الشفت الليلي
                        }
                        $totalDurationHours += $start->diffInMinutes($end) / 60;
                    }
                }
            }
            $totalDurationHours = round($totalDurationHours, 2);
        }

        // 6. إعادة حساب إحصائيات العرض (لكي تظهر للمستخدم أرقام الأيام المفلترة فقط)
        $displayStats = HelperFunctions::calculateAttendanceStats($result);
        $displayStats['weekly_leave_calculation'] = $payrollCalculation;

        $result->put('statistics', $displayStats);
        $result->put('total_duration_hours', $totalDurationHours);

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

        // dd($totalMissingHoursSeconds);
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
        $minEarlyDepartureMinutes = (int) \App\Models\Setting::getSetting('early_depature_deduction_minutes', 0);
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
                        // Only count if minutes >= minimum threshold from settings
                        if (!$employee->discount_exception_if_attendance_late  && $minutes >= $minEarlyDepartureMinutes && $minutes > 0) {

                            $shouldDeduct = true;
                            if (setting('flix_hours_early_departure')) {
                                if (
                                    isset($lastCheckout['total_actual_duration_hourly']) &&
                                    isset($lastCheckout['supposed_duration_hourly'])
                                ) {
                                    $helper = new \App\Services\HR\AttendanceHelpers\Reports\HelperFunctions();
                                    $reflection = new \ReflectionClass($helper);
                                    $method = $reflection->getMethod('timeToHoursForLateArrival');
                                    $method->setAccessible(true);

                                    $actualHoursFloat = $method->invoke($helper, $lastCheckout['total_actual_duration_hourly']);
                                    $supposedHoursFloat = $method->invoke($helper, $lastCheckout['supposed_duration_hourly']);

                                    if ($actualHoursFloat >= ($supposedHoursFloat - (\App\Services\HR\AttendanceHelpers\Reports\HelperFunctions::FLEXIBLE_HOURS_MARGIN_MINUTES / 60))) {
                                        $shouldDeduct = false;
                                    }
                                }
                            }

                            if ($shouldDeduct) {
                                $totalEarlyDepartureSeconds += $minutes * 60;
                            }
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
        if (!$employee->discount_exception_if_attendance_late) {
            $result->put('total_early_departure_minutes', [
                'total_minutes' => $totalEarlyDepartureSeconds / 60,
                'formatted'     => $totalEarlyDeparture,
                'total_seconds' => $totalEarlyDepartureSeconds,
                'total_hours'   => round($totalEarlyDepartureSeconds / 3600, 2),
            ]);
        } else {
            $result->put('total_early_departure_minutes', [
                'total_minutes' => 0,
                'formatted'     => '00:00:00',
                'total_seconds' => 0,
                'total_hours'   => 0,
            ]);
        }

        if (!$employee->discount_exception_if_attendance_late) {
            $result->put('late_hours',  $this->helperFunctions->calculateTotalLateArrival($result));
        } else {
            $result->put('late_hours', [
                'totalMinutes' => 0,
                'totalHoursFloat' => 0,
            ]);
        }

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

    /**
     * تحويل أيام الغياب إلى إجازة أسبوعية تلقائية
     * القاعدة: كل 6 أيام عمل = يوم إجازة مستحق
     * 
     * المنطق الجديد (نظام الاقتراض):
     * - يمكن للموظف أخذ إجازته في أي وقت خلال الدورة (6 عمل + 1 إجازة)
     * - إذا غاب الموظف قبل إكمال 6 أيام عمل، يُحسب كـ "اقتراض" للإجازة
     * - بعد الاقتراض، يحتاج لإكمال 6 أيام عمل للتأهل لإجازة جديدة
     * - إذا غاب مرة أخرى قبل إكمال 6 أيام عمل، يُحسب كغياب حقيقي
     */
    protected function applyWeeklyLeaveToAbsences(Collection $result, bool $isPreviousMonth = true): Collection
    {
        $workDaysPerLeave = \App\Modules\HR\Overtime\WeeklyLeaveCalculator\WeeklyLeaveCalculator::WORK_DAYS_PER_LEAVE;

        // جمع التواريخ فقط (استبعاد الإحصائيات والمفاتيح غير التواريخ)
        $dates = $result->keys()->filter(function ($key) {
            return preg_match('/^\d{4}-\d{2}-\d{2}$/', $key);
        })->sort()->values();

        // =========================================================================
        // المرحلة الأولى: حساب إجمالي أيام العمل وأيام الغياب
        // =========================================================================
        $totalWorkDays = 0;
        $absentDates = [];

        foreach ($dates as $date) {
            $day = $result->get($date);
            if (!is_array($day) || !isset($day['day_status'])) {
                continue;
            }

            $status = $day['day_status'];

            // إذا كان حاضراً، احسب كيوم عمل
            if (in_array($status, [
                AttendanceReportStatus::Present->value,
                AttendanceReportStatus::IncompleteCheckoutOnly->value,
            ])) {
                $totalWorkDays++;
            }
            // إذا كان غائباً أو لديه حضور جزئي أو حضور بلا انصراف، أضفه لقائمة الغيابات (حسب الإعدادات)
            elseif (
                $status === AttendanceReportStatus::Absent->value ||
                ((\App\Models\Setting::getSetting('count_partial_as_absent') ?? true) && in_array($status, [
                    AttendanceReportStatus::Partial->value,
                    AttendanceReportStatus::IncompleteCheckinOnly->value,
                    AttendanceReportStatus::IncompleteCheckoutOnly->value,
                ]))
            ) {
                $absentDates[] = $date;
            }
        }

        // dd($absentDates, $totalWorkDays);
        // =========================================================================
        // حساب إجمالي الإجازات المستحقة
        // =========================================================================
        // كل 6 أيام عمل = يوم إجازة مستحق
        $totalEntitledLeaves = floor($totalWorkDays / $workDaysPerLeave);
        $workDaysTowardsNext = $totalWorkDays % $workDaysPerLeave;

        // عدد الغيابات التي يمكن تحويلها = الإجازات المستحقة (بحد أقصى عدد الغيابات)
        $leavesToUse = min($totalEntitledLeaves, count($absentDates));

        // =========================================================================
        // المرحلة الثانية: تحويل الغيابات إلى إجازة أسبوعية
        // =========================================================================
        $usedLeaves = 0;

        if ($isPreviousMonth) {
            foreach ($absentDates as $date) {
                if ($usedLeaves >= $leavesToUse) {
                    break; // استهلكنا كل الإجازات المتاحة
                }

                $day = $result->get($date);

                $day['day_status'] = AttendanceReportStatus::WeeklyLeave->value;
                $day['weekly_leave_auto'] = true;
                $day['leave_type'] = 'Weekly Leave (Auto)';

                // تحديث final_status داخل كل period
                if (isset($day['periods'])) {
                    $periods = $day['periods'];
                    if ($periods instanceof \Illuminate\Support\Collection) {
                        $periods = $periods->toArray();
                    }
                    foreach ($periods as $key => $period) {
                        $periods[$key]['final_status'] = AttendanceReportStatus::WeeklyLeave->value;
                    }
                    $day['periods'] = $periods;
                }

                $result->put($date, $day);
                $usedLeaves++;
            }
        }

        // =========================================================================
        // إضافة إحصائيات الإجازة الأسبوعية
        // =========================================================================
        $remainingAbsences = count($absentDates) - $usedLeaves;

        $result->put('weekly_leave_stats', [
            'total_work_days' => $totalWorkDays,
            'entitled_leaves' => $totalEntitledLeaves,
            'used_leaves' => $usedLeaves,
            'remaining_leaves' => $totalEntitledLeaves - $usedLeaves,
            'remaining_absences' => $remainingAbsences,
            'work_days_towards_next' => $workDaysTowardsNext,
        ]);

        return $result;
    }
    /**
     * نسخة محسنة من جلب الحضور تدعم البيانات المحملة مسبقاً (Batch)
     */
    public function fetchEmployeeAttendancesBatch(
        Employee $employee,
        Carbon $date,
        array $preloadedData = []
    ): Collection {
        $dateString = $date->toDateString();

        // 1. استخدام البيانات المحملة مسبقاً أو جلبها إذا لم توجد (لضمان التوافق)
        $periodsByDay  = $preloadedData['periods'] ?? $this->periodHistoryService->getEmployeePeriodsByDateRange($employee, $date, $date);
        $leaveApplications = $preloadedData['leaves'] ?? $this->getEmployeeLeaves($employee, $date, $date);
        $termination   = $preloadedData['termination'] ?? $employee->serviceTermination()->where('status', \App\Models\EmployeeServiceTermination::STATUS_APPROVED)->first();
        $allAttendances = $preloadedData['attendances'] ?? collect();
        $allOvertimes   = $preloadedData['overtimes'] ?? collect();

        $result = collect();

        // منطق إنهاء الخدمة
        if ($termination && $date->gt($termination->termination_date)) {
            $result->put($dateString, [
                'date'          => $dateString,
                'day_name'      => $date->translatedFormat('l'),
                'periods'       => collect(),
                'day_status'    => AttendanceReportStatus::Terminated->value,
                'leave_type'    => 'Terminated',
                'leave_type_id' => null,
            ]);
            return $result;
        }

        // منطق الإجازات
        $leaveFound = $leaveApplications->first(fn($l) => $date->between($l->from_date, $l->to_date));
        if ($leaveFound) {
            $result->put($dateString, [
                'date'          => $dateString,
                'day_name'      => $date->translatedFormat('l'),
                'periods'       => [],
                'day_status'    => AttendanceReportStatus::Leave->value,
                'leave_type'    => $leaveFound->transaction_description,
                'leave_type_id' => $leaveFound->leave_type,
            ]);
            return $result;
        }

        $periods            = collect();
        $overtimeCalculator = new \App\Services\HR\AttendanceHelpers\Reports\AttendanceOvertimeCalculator();

        $dayPeriods = $periodsByDay['days'][$dateString]['periods'] ?? collect();

        foreach ($dayPeriods as $period) {
            $attendanceRecords = $allAttendances->where('period_id', $period['period_id'])
                ->sortBy('check_time')
                ->values();

            $checkInCollection  = $attendanceRecords->where('check_type', Attendance::CHECKTYPE_CHECKIN)->values();
            $checkOutCollection = $attendanceRecords->where('check_type', Attendance::CHECKTYPE_CHECKOUT)->values();

            // حساب الساعات الإضافية المعتمدة من البيانات المحملة
            $approvedOvertimeHours = $allOvertimes->where('period_id', $period['period_id'])->sum('hours');
            $approvedOvertime = $overtimeCalculator->formatFloatToDuration($approvedOvertimeHours);

            // استكمال معالجة Resources (مبسطة لتجنب N+1 داخل الـ Resources)
            $lastCheckoutModel    = $checkOutCollection->last();
            $lastCheckoutResource = $lastCheckoutModel ? (new CheckOutAttendanceResource($lastCheckoutModel, $approvedOvertime, $dateString))->toArray(request()) : null;

            if ($lastCheckoutResource) {
                $lastCheckoutResource['period_end_at'] = $period['end_time'];
            }

            $checkInResources = $checkInCollection->map(function ($item) use ($lastCheckoutResource) {
                return (new CheckInAttendanceResource($item, $lastCheckoutResource))->toArray(request());
            })->all();

            $checkIn = $checkInResources;
            $firstCheckoutModel    = $checkOutCollection->first();
            $firstCheckoutResource = $firstCheckoutModel ? (new CheckOutAttendanceResource($firstCheckoutModel))->toArray(request()) : null;

            if ($firstCheckoutResource) {
                $firstCheckoutResource['period_end_at'] = $period['end_time'];
                $checkIn['firstcheckout']                      = $firstCheckoutResource;
                $checkIn['firstcheckout']['approved_overtime'] = $approvedOvertime;
            }

            $checkOutResources = $checkOutCollection->map(function ($item) use ($employee, $period, $dateString, $overtimeCalculator, $approvedOvertime) {
                return (new CheckOutAttendanceResource($item, $approvedOvertime, $dateString))->toArray(request());
            })->all();

            $checkOut = $checkOutResources;
            if ($lastCheckoutResource) {
                $checkOut['lastcheckout']                      = $lastCheckoutResource;
                $checkOut['lastcheckout']['approved_overtime'] = $approvedOvertime;
            }

            $hasCheckin  = count($checkInResources) > 0;
            $hasCheckout = count($checkOutResources) > 0;
            $isFutureDate  = $date->gt(Carbon::today());
            $isShiftNotYetStarted = $date->isToday() && Carbon::now()->lt(Carbon::parse("{$dateString} {$period['start_time']}"));

            if ($isFutureDate || $isShiftNotYetStarted) {
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
                'attendances'  => ['checkin' => $checkIn, 'checkout' => $checkOut],
                'final_status' => $status->value,
            ]);
        }

        $allPeriodsStatus = $periods->pluck('final_status')->all();
        if (empty($allPeriodsStatus)) {
            $dayStatus = AttendanceReportStatus::NoPeriods->value;
        } elseif (count(array_unique($allPeriodsStatus)) === 1) {
            $dayStatus = $allPeriodsStatus[0];
        } else {
            $dayStatus = AttendanceReportStatus::Partial->value;
        }

        $actualSeconds = 0;
        foreach ($periods as $p) {
            $lastCheckout = $p['attendances']['checkout']['lastcheckout'] ?? null;
            if ($lastCheckout && ! empty($lastCheckout['total_actual_duration_hourly'])) {
                list($h, $m, $s) = explode(':', $lastCheckout['total_actual_duration_hourly']);
                $actualSeconds += ($h * 3600) + ($m * 60) + $s;
            }
        }

        $result->put($dateString, [
            'date'                  => $dateString,
            'day_name'              => $date->translatedFormat('l'),
            'periods'               => $periods,
            'actual_duration_hours' => gmdate('H:i:s', $actualSeconds),
            'day_status'            => $dayStatus,
        ]);

        // Apply weekly leave if applicable (for previous month and enabled for employee)
        $isPreviousMonth = $date->format('Y-m') < now()->format('Y-m');
        if ($isPreviousMonth && $employee->has_auto_weekly_leave) {
            $result = $this->applyWeeklyLeaveToAbsences($result, $isPreviousMonth);
        }

        // Calculate statistics for the result
        $this->calculateStatisticsBatch($employee, $result, $dayPeriods);

        return $result;
    }

    /**
     * حساب إحصائيات الحضور لمجموعة نتائج (نسخة مبسطة تعتمد على الكود الأصلي)
     */
    protected function calculateStatisticsBatch(Employee $employee, Collection $result, Collection $periodsForStats): void
    {
        // 1. حساب إجمالي الساعات المفترضة
        $totalDurationHours = 0;
        foreach ($periodsForStats as $period) {
            $start = \Carbon\Carbon::parse($period['start_time']);
            $end = \Carbon\Carbon::parse($period['end_time']);
            if ($end->lessThan($start)) {
                $end->addDay();
            }
            $totalDurationHours += $start->diffInMinutes($end) / 60;
        }
        $totalDurationHours = round($totalDurationHours, 2);

        // 2. إحصائيات الحضور الأساسية
        $displayStats = HelperFunctions::calculateAttendanceStats($result);
        
        // ملاحظة: حساب الإجازة الأسبوعية (payrollCalculation) يتم عادة في المدى الطويل
        // هنا سنضيفها كخالية إذا لم تكن موجودة أو نحاذي المنطق الأصلي
        $result->put('statistics', $displayStats);
        $result->put('total_duration_hours', $totalDurationHours);

        // 3. إجمالي الساعات الفعلية
        $totalActualSeconds = 0;
        foreach ($result as $key => $day) {
            if (is_array($day) && isset($day['actual_duration_hours']) && preg_match('/^\d{2}:\d{2}:\d{2}$/', $day['actual_duration_hours'])) {
                list($h, $m, $s) = explode(':', $day['actual_duration_hours']);
                $totalActualSeconds += ($h * 3600) + ($m * 60) + $s;
            }
        }
        $result->put('total_actual_duration_hours', gmdate('H:i:s', $totalActualSeconds));

        // 4. إضافي الساعات المعتمد
        $totalApprovedOvertimeSeconds = 0;
        foreach ($result as $day) {
            if (is_array($day) && isset($day['periods'])) {
                foreach ($day['periods'] as $period) {
                    $lastCheckout = $period['attendances']['checkout']['lastcheckout'] ?? null;
                    if ($lastCheckout && !empty($lastCheckout['approved_overtime'])) {
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
        $result->put('total_approved_overtime', gmdate('H:i:s', $totalApprovedOvertimeSeconds));

        // 5. الساعات الناقصة
        $totalMissingHoursSeconds = 0;
        foreach ($result as $day) {
            if (is_array($day) && isset($day['periods'])) {
                foreach ($day['periods'] as $period) {
                    $lastCheckout = $period['attendances']['checkout']['lastcheckout'] ?? null;
                    if ($lastCheckout && isset($lastCheckout['missing_hours']['total_minutes'])) {
                        $totalMissingHoursSeconds += ($lastCheckout['missing_hours']['total_minutes'] * 60);
                    }
                }
            }
        }
        $result->put('total_missing_hours', [
            'total_minutes' => $totalMissingHoursSeconds / 60,
            'formatted'     => gmdate('H:i:s', $totalMissingHoursSeconds),
            'total_seconds' => (float)$totalMissingHoursSeconds,
            'total_hours'   => round($totalMissingHoursSeconds / 3600, 2),
        ]);

        // 6. الانصراف المبكر
        $totalEarlyDepartureSeconds = 0;
        $minEarlyDepartureMinutes = (int) \App\Models\Setting::getSetting('early_depature_deduction_minutes', 0);
        foreach ($result as $day) {
            if (is_array($day) && isset($day['periods'])) {
                foreach ($day['periods'] as $period) {
                    $lastCheckout = $period['attendances']['checkout']['lastcheckout'] ?? null;
                    if ($lastCheckout && isset($lastCheckout['early_departure_minutes'])) {
                        $minutes = (int) $lastCheckout['early_departure_minutes'];
                        if (!$employee->discount_exception_if_attendance_late && $minutes >= $minEarlyDepartureMinutes && $minutes > 0) {
                            $totalEarlyDepartureSeconds += $minutes * 60;
                        }
                    }
                }
            }
        }
        $result->put('total_early_departure_minutes', [
            'total_minutes' => $totalEarlyDepartureSeconds / 60,
            'formatted'     => gmdate('H:i:s', $totalEarlyDepartureSeconds),
            'total_seconds' => $totalEarlyDepartureSeconds,
            'total_hours'   => round($totalEarlyDepartureSeconds / 3600, 2),
        ]);

        // 7. التأخير
        if (!$employee->discount_exception_if_attendance_late) {
            $result->put('late_hours', $this->helperFunctions->calculateTotalLateArrival($result));
        } else {
            $result->put('late_hours', ['totalMinutes' => 0, 'totalHoursFloat' => 0]);
        }
    }
}
