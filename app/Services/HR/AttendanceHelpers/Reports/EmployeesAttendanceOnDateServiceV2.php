<?php

namespace App\Services\HR\AttendanceHelpers\Reports;

use App\Enums\HR\Attendance\AttendanceReportStatus;
use App\Http\Resources\CheckInAttendanceResource;
use App\Http\Resources\CheckOutAttendanceResource;
use App\Models\Attendance;
use App\Models\Employee;
use App\Models\EmployeeOvertime;
use App\Models\EmployeePeriodHistory;
use App\Models\EmployeeServiceTermination;
use App\Models\WorkPeriod;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * V2 - Fully Optimised (Batch Query Edition)
 *
 * الهدف: تقليل عدد الاستعلامات من 750+ إلى 6 استعلامات لأي عدد من الموظفين
 *
 * الاستعلامات الست:
 *  1. Employee + branch filter
 *  2. EmployeePeriodHistory (جميع الموظفين دفعة واحدة) + with('workPeriod')
 *  3. Attendance (جميع الموظفين/الفترات في تاريخ محدد، دفعة واحدة)
 *  4. EmployeeApplicationV2 (إجازات معتمدة) + hr_leave_requests + hr_leave_types
 *  5. EmployeeServiceTermination (إنهاءات الخدمة)
 *  6. EmployeeOvertime (أوفر تايم معتمد)
 *  + WorkPeriods مُضمَّنة في العلاقة (لا استعلام إضافي)
 */
class EmployeesAttendanceOnDateServiceV2
{
    // ─────────────────────────────────────────────────────────────────────────
    // Cache لإعدادات النظام (تُجلب مرة واحدة لكامل الطلب)
    // ─────────────────────────────────────────────────────────────────────────
    private int   $graceArrivalMinutes;
    private int   $graceDepartureMinutes;
    private bool  $flexHoursEarlyDeparture;
    private bool  $countPartialAsAbsent;
    private int   $minEarlyDepartureMinutes;

    // ─────────────────────────────────────────────────────────────────────────
    // الدالة العامة الرئيسية
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * @param  array|Collection  $employeeIdsOrEmployees  معرّفات الموظفين أو Collection<Employee>
     * @param  Carbon|string     $date
     * @return Collection  [employee_id => ['employee'=>..., 'attendance_report'=>...]]
     */
    public function fetchAttendances($employeeIdsOrEmployees, $date): Collection
    {
        $date = $date instanceof Carbon ? $date : Carbon::parse($date);
        $dateStr = $date->toDateString();

        // ── 0. تحميل إعدادات النظام مرة واحدة ──────────────────────────────
        $this->loadSettings();

        // ── 1. جلب الموظفين ─────────────────────────────────────────────────
        if (
            $employeeIdsOrEmployees instanceof Collection
            && $employeeIdsOrEmployees->first() instanceof Employee
        ) {
            $employees = $employeeIdsOrEmployees;
        } else {
            $ids = is_array($employeeIdsOrEmployees)
                ? $employeeIdsOrEmployees
                : collect($employeeIdsOrEmployees)->toArray();

            $employees = Employee::whereIn('id', $ids)->get([
                'id',
                'name',
                'branch_id',
                'has_auto_weekly_leave',
                'discount_exception_if_attendance_late',
            ]);
        }

        if ($employees->isEmpty()) {
            return collect();
        }

        $employeeIds = $employees->pluck('id')->toArray();

        // ── 2. جلب الفترات التاريخية لجميع الموظفين في استعلام واحد ─────────
        // نحتاج الفترات التي تشمل تاريخ $date فقط
        $allHistories = EmployeePeriodHistory::with('workPeriod')
            ->where('active', 1)
            ->whereIn('employee_id', $employeeIds)
            ->where('start_date', '<=', $dateStr)
            ->where(function ($q) use ($dateStr) {
                $q->whereNull('end_date')->orWhere('end_date', '>=', $dateStr);
            })
            ->get()
            ->groupBy('employee_id');  // Collection مُجمَّعة بـ employee_id

        // ── 3. جلب سجلات الحضور المقبولة لتاريخ $date لجميع الموظفين ────────
        $allAttendances = Attendance::where('deleted_at', null)
            ->where('accepted', 1)
            ->whereIn('employee_id', $employeeIds)
            ->whereDate('check_date', $dateStr)
            ->orderBy('id')
            ->get()
            ->groupBy('employee_id');  // Collection مُجمَّعة بـ employee_id

        // ── 4. جلب الإجازات المعتمدة لجميع الموظفين ─────────────────────────
        $allLeaves = \DB::table('hr_employee_applications')
            ->join('hr_leave_requests', 'hr_employee_applications.id', '=', 'hr_leave_requests.application_id')
            ->join('hr_leave_types', 'hr_leave_requests.leave_type', '=', 'hr_leave_types.id')
            ->where('hr_employee_applications.application_type_id', 1)
            ->where('hr_employee_applications.status', 'approved')
            ->whereIn('hr_employee_applications.employee_id', $employeeIds)
            ->where(function ($q) use ($dateStr) {
                $q->where('hr_leave_requests.start_date', '<=', $dateStr)
                    ->where('hr_leave_requests.end_date', '>=', $dateStr);
            })
            ->select(
                'hr_employee_applications.employee_id',
                'hr_leave_requests.start_date as from_date',
                'hr_leave_requests.end_date as to_date',
                'hr_leave_requests.leave_type',
                'hr_leave_types.name as transaction_description'
            )
            ->get()
            ->groupBy('employee_id');

        // ── 5. جلب إنهاءات الخدمة المعتمدة ───────────────────────────────────
        $allTerminations = \DB::table('hr_employee_service_terminations')
            ->whereIn('employee_id', $employeeIds)
            ->where('status', EmployeeServiceTermination::STATUS_APPROVED)
            ->select('employee_id', 'termination_date')
            ->get()
            ->keyBy('employee_id');

        // ── 6. جلب الأوفر تايم المعتمد لتاريخ $date لجميع الموظفين ──────────
        // ملاحظة: hr_employee_overtime لا يحتوي على period_id — الأوفر تايم بالتاريخ فقط
        $allOvertimes = EmployeeOvertime::whereIn('employee_id', $employeeIds)
            ->where('status', EmployeeOvertime::STATUS_APPROVED)
            ->whereDate('date', $dateStr)
            ->select('employee_id', 'hours', 'date')
            ->get()
            ->groupBy('employee_id');

        // ── 7. جلب جميع WorkPeriods المُستخدمة مرة واحدة ────────────────────
        $workPeriodIds = $allHistories->flatten()->pluck('period_id')->unique()->toArray();
        $workPeriodMap = WorkPeriod::whereIn('id', $workPeriodIds)
            ->get(['id', 'name', 'start_at', 'end_at', 'day_and_night'])
            ->keyBy('id');

        // ── 8. بناء نتيجة كل موظف من الذاكرة (بلا استعلامات إضافية) ─────────
        $results = collect();
        $dayName = $date->translatedFormat('l');
        $currentDayShort = strtolower($date->format('D')); // 'mon', 'tue', ...
        $isToday    = $date->isToday();
        $isFuture   = $date->gt(Carbon::today());
        $nowCarbon  = Carbon::now();

        foreach ($employees as $employee) {
            $empId = $employee->id;

            // تحقق من إنهاء الخدمة
            $termination = $allTerminations->get($empId);
            if ($termination && Carbon::parse($termination->termination_date)->lt($date)) {
                $results->put($empId, [
                    'employee'          => ['id' => $empId, 'name' => $employee->name],
                    'attendance_report' => $this->buildTerminatedReport($dateStr, $dayName),
                ]);
                continue;
            }

            // تحقق من الإجازة
            $leave = ($allLeaves->get($empId) ?? collect())->first();
            if ($leave) {
                $results->put($empId, [
                    'employee'          => ['id' => $empId, 'name' => $employee->name],
                    'attendance_report' => $this->buildLeaveReport($dateStr, $dayName, $leave),
                ]);
                continue;
            }

            // فترات الموظف لهذا اليوم (فلترة في الذاكرة)
            $empHistories = ($allHistories->get($empId) ?? collect())->filter(function ($h) use ($currentDayShort, $dateStr) {
                $dayVal      = is_object($h->day_of_week) && property_exists($h->day_of_week, 'value')
                    ? $h->day_of_week->value
                    : $h->day_of_week;
                $dayMatch    = $dayVal === $currentDayShort;
                $startOk     = $h->start_date <= $dateStr;
                $endOk       = is_null($h->end_date) || $h->end_date >= $dateStr;
                return $dayMatch && $startOk && $endOk;
            });

            // سجلات الحضور للموظف (مُجلبة مسبقاً) مُقسَّمة حسب period_id
            $empAttendances = ($allAttendances->get($empId) ?? collect())->groupBy('period_id');

            // الأوفر تايم للموظف في هذا التاريخ (جميع الفترات مجموعة)
            $empOvertimes = ($allOvertimes->get($empId) ?? collect());

            // حساب الفترات
            $periods = collect();
            foreach ($empHistories->values() as $history) {
                $periodId = $history->period_id;
                $workPeriod = $workPeriodMap->get($periodId);
                if (!$workPeriod) {
                    continue;
                }

                $startTime      = $history->start_time ?? $workPeriod->start_at;
                $endTime        = $history->end_time   ?? $workPeriod->end_at;
                $supposedDur    = $this->calcSupposedDuration($startTime, $endTime, (bool)$workPeriod->day_and_night);

                // بيانات الفترة
                $periodData     = [
                    'period_id'         => $periodId,
                    'name'              => $workPeriod->name,
                    'start_time'        => $startTime,
                    'end_time'          => $endTime,
                    'supposed_duration' => $supposedDur,
                ];

                // سجلات الحضور لهذه الفترة (من الذاكرة)
                $periodAttendances = ($empAttendances->get($periodId) ?? collect())->sortBy('id')->values();

                $checkInCollection  = $periodAttendances->where('check_type', Attendance::CHECKTYPE_CHECKIN)->sortBy('id')->values();
                $checkOutCollection = $periodAttendances->where('check_type', Attendance::CHECKTYPE_CHECKOUT)->sortBy('id')->values();

                // حساب الأوفر تايم من الذاكرة (مجموع كل الأوفر تايم لهذا اليوم)
                $approvedOvertime = $this->calcApprovedOvertimeFromMemory(
                    $periodAttendances,
                    $workPeriod,
                    $empOvertimes  // كل أوفر تايم اليوم (ليس مُقسَّماً بالفترة)
                );

                // بناء Resources
                $checkOutResources = $checkOutCollection->map(function ($item) use ($approvedOvertime, $dateStr) {
                    return (new CheckOutAttendanceResource($item, $approvedOvertime, $dateStr))->toArray(request());
                })->all();

                $lastCheckoutModel    = $checkOutCollection->last();
                $lastCheckoutResource = $lastCheckoutModel
                    ? (new CheckOutAttendanceResource($lastCheckoutModel, $approvedOvertime, $dateStr))->toArray(request())
                    : null;

                if ($lastCheckoutResource) {
                    $lastCheckoutResource['period_end_at'] = $endTime;
                }

                $checkInResources = $checkInCollection->map(function ($item) use ($lastCheckoutResource) {
                    return (new CheckInAttendanceResource($item, $lastCheckoutResource))->toArray(request());
                })->all();

                $firstCheckoutModel    = $checkOutCollection->first();
                $firstCheckoutResource = $firstCheckoutModel
                    ? (new CheckOutAttendanceResource($firstCheckoutModel))->toArray(request())
                    : null;

                $checkIn = $checkInResources;
                if ($firstCheckoutResource) {
                    $firstCheckoutResource['period_end_at']      = $endTime;
                    $checkIn['firstcheckout']                    = $firstCheckoutResource;
                    $checkIn['firstcheckout']['approved_overtime'] = $approvedOvertime;
                }

                $checkOut = $checkOutResources;
                if ($lastCheckoutResource) {
                    $checkOut['lastcheckout']                      = $lastCheckoutResource;
                    $checkOut['lastcheckout']['approved_overtime'] = $approvedOvertime;
                }

                $groupedAttendances = ['checkin' => $checkIn, 'checkout' => $checkOut];

                // تحديد الحالة
                $hasCheckin  = count($checkInResources)  > 0;
                $hasCheckout = count($checkOutResources) > 0;

                $shiftStartDatetime = Carbon::parse("{$dateStr} {$startTime}");
                $isShiftNotYetStarted = $isToday && $nowCarbon->lt($shiftStartDatetime);

                if ($isFuture || $isShiftNotYetStarted) {
                    $status = AttendanceReportStatus::Future;
                } elseif (!$hasCheckin && !$hasCheckout) {
                    $status = AttendanceReportStatus::Absent;
                } elseif ($hasCheckin && !$hasCheckout) {
                    $status = AttendanceReportStatus::IncompleteCheckinOnly;
                } elseif (!$hasCheckin && $hasCheckout) {
                    $status = AttendanceReportStatus::IncompleteCheckoutOnly;
                } else {
                    $status = AttendanceReportStatus::Present;
                }

                $periods->push([
                    'period_id'    => $periodId,
                    'period_name'  => $workPeriod->name,
                    'start_time'   => $startTime,
                    'end_time'     => $endTime,
                    'attendances'  => $groupedAttendances,
                    'final_status' => $status->value,
                ]);
            }

            // حالة اليوم الإجمالية
            $allPeriodsStatus = $periods->pluck('final_status')->all();
            $dayStatus = $this->resolveDayStatus($allPeriodsStatus);

            // مدة العمل الفعلية
            $actualSeconds = 0;
            foreach ($periods as $period) {
                $lastCheckout = $period['attendances']['checkout']['lastcheckout'] ?? null;
                if ($lastCheckout && !empty($lastCheckout['total_actual_duration_hourly'])) {
                    [$h, $m, $s] = explode(':', $lastCheckout['total_actual_duration_hourly']);
                    $actualSeconds += ($h * 3600) + ($m * 60) + $s;
                }
            }
            $actualDuration = gmdate('H:i:s', $actualSeconds);

            $dayReport = [
                'date'                  => $dateStr,
                'day_name'              => $dayName,
                'periods'               => $periods,
                'actual_duration_hours' => $actualDuration,
                'day_status'            => $dayStatus,
            ];

            // ── بناء Collection التقرير مع كامل الإحصائيات ──────────────────
            $report = collect([$dateStr => $dayReport]);

            // ── statistics: ملخص الأيام (حاضر، غائب، إجازة، ...) ─────────────
            $report->put('statistics', HelperFunctions::calculateAttendanceStats($report));

            // ── total_duration_hours: إجمالي الساعات المفترضة ────────────────
            $totalDurationSeconds = 0;
            foreach ($periods as $p) {
                if (!empty($p['supposed_duration'])) {
                    [$dh, $dm, $ds] = explode(':', $p['supposed_duration']);
                    $totalDurationSeconds += ($dh * 3600) + ($dm * 60) + $ds;
                }
            }
            $report->put('total_duration_hours', round($totalDurationSeconds / 3600, 2));

            // ── إجمالي الساعات الفعلية ────────────────────────────────────────
            $report->put('total_actual_duration_hours', $actualDuration);

            // ── إجمالي الإضافي + الناقص + الانصراف المبكر (من بيانات الذاكرة) ─
            $totalApprovedOvertimeSeconds = 0;
            $totalMissingSeconds = 0;
            $totalEarlyDepartureSeconds = 0;

            foreach ($periods as $p) {
                $lastCo = $p['attendances']['checkout']['lastcheckout'] ?? null;
                if (!$lastCo) continue;

                // إضافي
                if (!empty($lastCo['approved_overtime'])) {
                    $val = $lastCo['approved_overtime'];
                    if (preg_match('/^(\d+):(\d+):(\d+)$/', $val, $mx)) {
                        $totalApprovedOvertimeSeconds += ($mx[1] * 3600) + ($mx[2] * 60) + $mx[3];
                    } else {
                        $h = 0; $m = 0; $s = 0;
                        if (preg_match('/(\d+)\s*h/', $val, $mh)) $h = (int)$mh[1];
                        if (preg_match('/(\d+)\s*m/', $val, $mm)) $m = (int)$mm[1];
                        if (preg_match('/(\d+)\s*s/', $val, $ms)) $s = (int)$ms[1];
                        $totalApprovedOvertimeSeconds += ($h * 3600) + ($m * 60) + $s;
                    }
                }

                // ناقص
                if (isset($lastCo['missing_hours']['total_minutes'])) {
                    $totalMissingSeconds += (int)($lastCo['missing_hours']['total_minutes'] * 60);
                }

                // انصراف مبكر — مع مراعاة الحد الأدنى وإعداد الموظف
                if (!$employee->discount_exception_if_attendance_late && isset($lastCo['early_departure_minutes'])) {
                    $edMins = (int)$lastCo['early_departure_minutes'];
                    if ($edMins >= $this->minEarlyDepartureMinutes && $edMins > 0) {
                        $shouldDeduct = true;
                        if ($this->flexHoursEarlyDeparture) {
                            if (isset($lastCo['total_actual_duration_hourly']) && isset($lastCo['supposed_duration_hourly'])) {
                                $helper = new \App\Services\HR\AttendanceHelpers\Reports\HelperFunctions();
                                $reflection = new \ReflectionClass($helper);
                                $method = $reflection->getMethod('timeToHoursForLateArrival');
                                $method->setAccessible(true);

                                $actualHoursFloat = $method->invoke($helper, $lastCo['total_actual_duration_hourly']);
                                $supposedHoursFloat = $method->invoke($helper, $lastCo['supposed_duration_hourly']);

                                if ($actualHoursFloat >= ($supposedHoursFloat - (\App\Services\HR\AttendanceHelpers\Reports\HelperFunctions::FLEXIBLE_HOURS_MARGIN_MINUTES / 60))) {
                                    $shouldDeduct = false;
                                }
                            }
                        }
                        
                        if ($shouldDeduct) {
                            $totalEarlyDepartureSeconds += $edMins * 60;
                        }
                    }
                }
            }

            $report->put('total_approved_overtime', $this->secsToHMS($totalApprovedOvertimeSeconds));

            $report->put('total_missing_hours', [
                'total_minutes' => $totalMissingSeconds / 60,
                'formatted'     => $this->secsToHMS($totalMissingSeconds),
                'total_seconds' => (float) $totalMissingSeconds,
                'total_hours'   => round($totalMissingSeconds / 3600, 2),
            ]);

            if (!$employee->discount_exception_if_attendance_late) {
                $report->put('total_early_departure_minutes', [
                    'total_minutes' => $totalEarlyDepartureSeconds / 60,
                    'formatted'     => $this->secsToHMS($totalEarlyDepartureSeconds),
                    'total_seconds' => $totalEarlyDepartureSeconds,
                    'total_hours'   => round($totalEarlyDepartureSeconds / 3600, 2),
                ]);
                $report->put('late_hours', (new HelperFunctions())->calculateTotalLateArrival($report));
            } else {
                $report->put('total_early_departure_minutes', [
                    'total_minutes' => 0, 'formatted' => '00:00:00',
                    'total_seconds' => 0, 'total_hours' => 0,
                ]);
                $report->put('late_hours', ['totalMinutes' => 0, 'totalHoursFloat' => 0]);
            }

            $results->put($empId, [
                'employee'          => ['id' => $empId, 'name' => $employee->name],
                'attendance_report' => $report,
            ]);
        }

        return $results;
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Private Helpers
    // ─────────────────────────────────────────────────────────────────────────

    private function loadSettings(): void
    {
        $this->graceArrivalMinutes      = (int) (settingWithDefault('early_attendance_minutes', 0));
        $this->graceDepartureMinutes    = (int) (settingWithDefault('early_depature_deduction_minutes', 0));
        $this->flexHoursEarlyDeparture  = (bool) setting('flix_hours_early_departure');
        $this->countPartialAsAbsent     = (bool) (setting('count_partial_as_absent') ?? true);
        $this->minEarlyDepartureMinutes = (int) (setting('early_depature_deduction_minutes', 0));
    }

    /**
     * تحسب approved_overtime من الذاكرة بدلاً من قاعدة البيانات
     * يستخدم البيانات المُجلبة مسبقاً (no DB queries)
     */
    private function calcApprovedOvertimeFromMemory(
        Collection $periodAttendances,
        WorkPeriod $workPeriod,
        Collection $periodOvertimes
    ): string {
        // حساب وقت العمل الفعلي من الذاكرة
        $totalMinutes = 0;
        $records = $periodAttendances->sortBy('id')->values();

        for ($i = 0; $i < $records->count(); $i++) {
            $current = $records[$i];
            if ($current->check_type !== Attendance::CHECKTYPE_CHECKIN) {
                continue;
            }
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

        // مدة الفترة المفترضة — تُحسب من start_at/end_at (supposed_duration هو Accessor لا عمود DB)
        $supposedHours = $this->getSupposedDurationHours($workPeriod);

        $isActualLargerThanSupposed = $actualHours > $supposedHours;

        // الأوفر تايم المعتمد (من الذاكرة)
        $approvedOvertimeHours = $periodOvertimes->sum('hours');

        if ($isActualLargerThanSupposed && $approvedOvertimeHours > 0) {
            return $this->formatFloatToHMS($approvedOvertimeHours + $supposedHours);
        } elseif ($isActualLargerThanSupposed) {
            return $this->formatFloatToHMS($supposedHours);
        } else {
            return $this->formatFloatToHMS($actualHours > 0 ? $actualHours + $approvedOvertimeHours : 0);
        }
    }

    /**
     * يحسب مدة الفترة المفترضة بالساعات من start_at و end_at
     * (supposed_duration هو Accessor في الموديل — لا يُخزَّن في DB)
     */
    private function getSupposedDurationHours(WorkPeriod $workPeriod): float
    {
        try {
            $start = Carbon::parse($workPeriod->start_at);
            $end   = Carbon::parse($workPeriod->end_at);
            if ($end->lte($start) || (bool) $workPeriod->day_and_night) {
                $end->addDay();
            }
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

    /**
     * تحويل ثوان إلى H:i:s — يدعم أكثر من 24 ساعة
     */
    private function secsToHMS(int $seconds): string
    {
        $h = floor($seconds / 3600);
        $m = floor(($seconds % 3600) / 60);
        $s = $seconds % 60;
        return sprintf('%02d:%02d:%02d', $h, $m, $s);
    }

    private function calcSupposedDuration(string $startTime, string $endTime, bool $dayAndNight): string
    {
        try {
            $start = Carbon::createFromFormat('H:i:s', Carbon::parse($startTime)->format('H:i:s'));
            $end   = Carbon::createFromFormat('H:i:s', Carbon::parse($endTime)->format('H:i:s'));
            if ($dayAndNight || $end->lte($start)) {
                $end->addDay();
            }
            return gmdate('H:i:s', $start->diffInSeconds($end));
        } catch (\Exception $e) {
            return '00:00:00';
        }
    }

    private function resolveDayStatus(array $allPeriodsStatus): string
    {
        if (empty($allPeriodsStatus)) {
            return AttendanceReportStatus::NoPeriods->value;
        }
        $unique = array_unique($allPeriodsStatus);
        if (count($unique) === 1) {
            $first = $unique[0];
            if ($first === AttendanceReportStatus::Future->value)  return AttendanceReportStatus::Future->value;
            if ($first === AttendanceReportStatus::Absent->value)  return AttendanceReportStatus::Absent->value;
            if ($first === AttendanceReportStatus::Present->value) return AttendanceReportStatus::Present->value;
        }
        return AttendanceReportStatus::Partial->value;
    }

    private function buildTerminatedReport(string $dateStr, string $dayName): Collection
    {
        return collect([
            $dateStr => [
                'date'       => $dateStr,
                'day_name'   => $dayName,
                'periods'    => collect(),
                'day_status' => AttendanceReportStatus::Terminated->value,
                'leave_type' => 'Terminated',
                'leave_type_id' => null,
            ],
        ]);
    }

    private function buildLeaveReport(string $dateStr, string $dayName, object $leave): Collection
    {
        return collect([
            $dateStr => [
                'date'       => $dateStr,
                'day_name'   => $dayName,
                'periods'    => [],
                'day_status' => AttendanceReportStatus::Leave->value,
                'leave_type' => $leave->transaction_description,
                'leave_type_id' => $leave->leave_type,
            ],
        ]);
    }
}
