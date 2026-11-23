<?php

namespace App\Traits;

use App\Models\AppLog;
use App\Models\Attendance;
use App\Models\Employee;
use App\Models\EmployeeOvertime;
use App\Models\EmployeePeriod;
use App\Models\Setting;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Trait containing attendance and overtime calculation methods for Employee model.
 */
trait EmployeeAttendanceTrait
{
    public function calculateTotalWorkHours($periodId, $date)
    {
        $attendances = $this->attendances()
            ->where('accepted', 1)
            ->where('period_id', $periodId)
            ->where('check_date', $date)
            ->orderBy('id')
            ->get();

        $totalMinutes = 0;

        for ($i = 0; $i < $attendances->count(); $i++) {
            $checkIn = $attendances[$i];

            if ($checkIn->check_type === 'checkin') {
                $i++;
                if ($i < $attendances->count()) {
                    $checkOut = $attendances[$i];

                    if ($checkOut->check_type === 'checkout') {
                        $checkInTime  = Carbon::parse("{$checkIn->check_date} {$checkIn->check_time}");
                        $checkOutTime = Carbon::parse("{$checkOut->check_date} {$checkOut->check_time}");

                        if ($checkOutTime < $checkInTime) {
                            $checkOutTime->addDay();
                        }

                        $totalMinutes += $checkInTime->diffInMinutes($checkOutTime);
                    }
                }
            }
        }

        $totalHours       = floor($totalMinutes / 60);
        $remainingMinutes = $totalMinutes % 60;

        $totalHours       = abs($totalHours);
        $remainingMinutes = abs($remainingMinutes);

        return "{$totalHours} h {$remainingMinutes} m";
    }

    public function calculateEmployeeOvertime($employee, $date)
    {
        if ($employee === null || empty($date)) {
            return [];
        }

        $dateStr = $date instanceof Carbon ? $date->toDateString() : Carbon::parse($date)->toDateString();
        $employeePeriods = $employee->periodHistoriesOnDate($dateStr);

        // Fetch all active (non-deleted) daily overtime records for this date (pending, approved, rejected)
        $existingOvertimes = EmployeeOvertime::query()
            ->where('employee_id', $employee->id)
            ->whereDate('date', $dateStr)
            ->where('type', EmployeeOvertime::TYPE_BASED_ON_DAY)
            ->get(['id', 'period_id', 'start_time', 'status']);

        $dayAttendances = $this->attendances()
            ->where('employee_id', $employee->id)
            ->where('check_date', $dateStr)
            ->accepted()
            ->orderBy('id')
            ->get();

        $noShiftValue = \App\Modules\HR\Attendance\Enums\AttendanceStatus::NO_SHIFT->value;
        $noShiftAttendances = $dayAttendances->filter(
            fn ($a) => $a->status === $noShiftValue || $a->period_id === null
        )->values();
        $hasNoShift = $noShiftAttendances->count() >= 2;

        // If a general whole-day overtime record (period_id = null) exists and there is no NO_SHIFT attendance, skip day
        if ($existingOvertimes->contains(fn ($ot) => $ot->period_id === null) && ! $hasNoShift) {
            return [];
        }

        $allowedOffset = Attendance::getMinutesByConstant(Setting::getSetting('period_allowed_to_calculate_overtime'));
        $halfHourRule = Setting::getSetting('period_allowed_to_calculate_overtime') == Attendance::PERIOD_ALLOWED_OVERTIME_HOUR
            && Setting::getSetting('calculating_overtime_with_half_hour_after_hour');

        $results = [];

        foreach ($employeePeriods as $employeePeriod) {
            $period = $employeePeriod->workPeriod;
            if (! $period) {
                continue;
            }

            // Skip this specific shift if it already has an overtime record
            if ($existingOvertimes->contains(fn ($ot) => $ot->period_id !== null && (int) $ot->period_id === (int) $period->id)) {
                continue;
            }

            $attendances = $dayAttendances->where('period_id', $period->id)->values();
            if ($attendances->count() < 2) {
                continue;
            }

            $totalMinutes = 0;
            $firstCheckInTime = null;
            $lastCheckOutTime = null;

            for ($i = 0; $i < $attendances->count(); $i++) {
                $checkIn = $attendances[$i];

                if ($checkIn->check_type === 'checkin') {
                    $i++;
                    if ($i < $attendances->count()) {
                        $checkOut = $attendances[$i];

                        if ($checkOut->check_type === 'checkout') {
                            $inDate = $checkIn->real_check_date ?: $checkIn->check_date;
                            $outDate = $checkOut->real_check_date ?: $checkOut->check_date;
                            $checkInTime  = Carbon::parse("{$inDate} {$checkIn->check_time}");
                            $checkOutTime = Carbon::parse("{$outDate} {$checkOut->check_time}");

                            if ($checkOutTime < $checkInTime) {
                                $checkOutTime->addDay();
                            }

                            $totalMinutes += $checkInTime->diffInMinutes($checkOutTime);
                            $firstCheckInTime = $firstCheckInTime ?? $checkInTime;
                            $lastCheckOutTime = $checkOutTime;
                        }
                    }
                }
            }

            if ($totalMinutes === 0 || ! $firstCheckInTime || ! $lastCheckOutTime) {
                continue;
            }

            // Skip if a legacy record (period_id = null) matches this shift's check-in HH:MM
            $firstInHm = substr($firstCheckInTime->toTimeString(), 0, 5);
            if ($existingOvertimes->contains(fn ($ot) => $ot->period_id === null && ! empty($ot->start_time) && substr((string) $ot->start_time, 0, 5) === $firstInHm)) {
                continue;
            }

            [$hours, $minutes] = explode(':', $period->supposed_duration);
            $supposedDurationMinutes = ((int) $hours * 60) + (int) $minutes;

            if ($totalMinutes >= ($supposedDurationMinutes + $allowedOffset)) {
                $overtimeMinutes = $totalMinutes - $supposedDurationMinutes;
                $overtimeHours    = round($overtimeMinutes / 60 * 2) / 2;
                $remainingMinutes = $overtimeMinutes % 60;

                $formattedOvertime = "{$overtimeHours} h {$remainingMinutes} m";

                if ($halfHourRule) {
                    $overtimeHours = round($overtimeHours, 2);
                }

                $results[] = [
                    'employee_id'               => $employee->id,
                    'period_id'                 => $period->id,
                    'period_name'               => $period->name,
                    'supposed_duration_minutes' => (int) $overtimeMinutes,
                    'overtime_hours'            => $overtimeHours,
                    'overtime'                  => $formattedOvertime,
                    'overtime_start_time'       => $firstCheckInTime->toTimeString(),
                    'overtime_end_time'         => $lastCheckOutTime->toTimeString(),
                    'check_in_time'             => $firstCheckInTime->toTimeString(),
                    'check_out_time'            => $lastCheckOutTime->toTimeString(),
                ];
            }
        }

        if ($hasNoShift && ! $existingOvertimes->contains(fn ($ot) => $ot->period_id === null)) {
            $noShiftOvertime = $this->calculateNoShiftOvertime($noShiftAttendances, (bool) $halfHourRule);
            if (! empty($noShiftOvertime)) {
                $results[] = $noShiftOvertime;
            }
        }

        return $results;
    }

    public function periodsOnDate(Carbon|string $date): Collection
    {
        $d = $date instanceof Carbon ? $date->copy() : Carbon::parse($date);
        $dow = $d->dayOfWeek;

        $employeeConnection = $this->getConnection()->getDatabaseName();
        $periodConnection = $this->employeePeriods()->getQuery()->getConnection()->getDatabaseName();
        $allPeriods = $this->employeePeriods()->count();
        $allDays = $this->periodDays()->count();


        $workPeriods = $this->employeePeriods()
            ->with(['workPeriod', 'days'])
            ->where(function ($q) use ($d) {
                $q->whereNull('end_date')->orWhere('end_date', '>=', $d->toDateString());
            })
            ->where(function ($q) use ($d) {
                $q->whereNull('start_date')->orWhere('start_date', '<=', $d->toDateString());
            })
            ->whereHas('days', function ($q) use ($dow, $d) {
                // Filter by day_of_week if needed
            })
            ->get();

        return $workPeriods;
    }

    public function periodHistoriesOnDate(Carbon|string $date): Collection
    {
        $d = $date instanceof Carbon ? $date->copy() : Carbon::parse($date);

        $workPeriods = $this->periodHistories()
            ->with(['workPeriod'])
            ->where('active', 1)
            ->where(function ($q) use ($d) {
                $q->whereNull('start_date')->orWhere('start_date', '<=', $d->toDateString());
            })
            ->where(function ($q) use ($d) {
                $q->whereNull('end_date')->orWhere('end_date', '>=', $d->toDateString());
            })
            ->get()
            ->unique('period_id')
            ->values();

        return $workPeriods;
    }

    public function isWorkingDay(Carbon|string $date): bool
    {
        return $this->periodsOnDate($date)->isNotEmpty();
    }

    public function createLinkedUser(array $data = []): ?User
    {
        if ($this->has_user) {
            return $this->user;
        }

        return DB::transaction(function () use ($data) {
            try {
                $managerUserId = $this->manager_id
                    ? Employee::find($this->manager_id)?->user_id
                    : null;

                // Ensure employee has email set in DB to avoid Observer crashing when syncing back to user
                if ((empty($this->email) || $this->email !== ($data['email'] ?? '')) && !empty($data['email'])) {
                    // Update email without triggering events? No, we need it to be in DB.
                    // But if we update it, Observer::updated will run.
                    // Let's create user first using data.
                }

                $userData = [
                    'name'         => $data['name'] ?? $this->name,
                    'email'        => $data['email'] ?? $this->email,
                    'phone_number' => $data['phone_number'] ?? $this->phone_number,
                    'password'     => bcrypt($data['password'] ?? '123456'),
                    'branch_id'    => $this->branch_id,
                    'user_type'    => 4,
                    'nationality'  => $this->nationality,
                    'gender'       => $this->gender,
                    'owner_id'     => $managerUserId,
                ];

                if ($this->avatar && (
                    Storage::disk('s3')->exists($this->avatar) ||
                    Storage::disk('public')->exists($this->avatar)
                )) {
                    $userData['avatar'] = $this->avatar;
                }

                $user = User::create($userData);

                $user->assignRole(8);
                $this->update(['user_id' => $user->id]);

                return $user;
            } catch (\Throwable $e) {
                throw $e;
            }
        });
    }


    /**
     * حساب إضافي الموظف ليوم محدد بالاعتماد كلياً على الـ Memory (Collections)
     * لا توجد أي استعلامات DB هنا، الأداء O(1)
     */
    public function calculateOvertimeInMemory(string $date, int $allowedOffset, bool $halfHourRule): array
    {
        // 1. جلب بصمات هذا اليوم فقط
        $attendances = $this->attendances
            ->where('check_date', $date)
            ->sortBy('id')
            ->values();

        if ($attendances->count() < 2) {
            return [];
        }

        // 2. جلب سجلات الإضافي المسجلة مسبقاً لهذا اليوم (جميع الحالات إن وجدت allDayOvertimes، أو المعتمدة overtimes)
        $existingSource = $this->relationLoaded('allDayOvertimes') ? $this->allDayOvertimes : $this->overtimes;
        $dayExistingOvertimes = $existingSource->where('date', $date)->values();

        $noShiftValue = \App\Modules\HR\Attendance\Enums\AttendanceStatus::NO_SHIFT->value;
        $noShiftAttendances = $attendances->filter(
            fn ($a) => $a->status === $noShiftValue || $a->period_id === null
        )->values();
        $hasNoShift = $noShiftAttendances->count() >= 2;

        // إذا وُجد إضافي عام لليوم (period_id = null) ولا يوجد دوام NO_SHIFT، نتخطى اليوم بالكامل
        if ($dayExistingOvertimes->contains(fn ($ot) => $ot->period_id === null) && ! $hasNoShift) {
            return [];
        }

        // 3. فلترة الفترات النشطة لهذا اليوم للورديات العادية مع إزالة تكرار أيام الأسبوع (unique period_id)
        $activePeriods = $this->periodHistories->filter(function ($history) use ($date) {
            $startValid = is_null($history->start_date) || $history->start_date <= $date;
            $endValid = is_null($history->end_date) || $history->end_date >= $date;
            return $startValid && $endValid;
        })->unique('period_id')->values();

        $results = [];

        // 4. حساب الإضافي لكل وردية على حدة بناءً على بصماتها فقط
        foreach ($activePeriods as $history) {
            $period = $history->workPeriod;
            if (! $period) {
                continue;
            }

            // تخطي هذه الوردية إذا كان لها سجل إضافي مسجل مسبقاً
            if ($dayExistingOvertimes->contains(fn ($ot) => $ot->period_id !== null && (int) $ot->period_id === (int) $period->id)) {
                continue;
            }

            // فلترة البصمات الخاصة بهذه الوردية فقط
            $periodAttendances = $attendances->where('period_id', $period->id)->values();

            if ($periodAttendances->count() < 2) {
                continue;
            }

            $totalMinutes = 0;
            $firstCheckInTime = null;
            $lastCheckOutTime = null;

            for ($i = 0; $i < $periodAttendances->count() - 1; $i++) {
                $current = $periodAttendances[$i];
                $next = $periodAttendances[$i + 1];

                if ($current->check_type === 'checkin' && $next->check_type === 'checkout') {
                    $inDate = $current->real_check_date ?: $current->check_date;
                    $outDate = $next->real_check_date ?: $next->check_date;
                    $in  = \Carbon\Carbon::parse("{$inDate} {$current->check_time}");
                    $out = \Carbon\Carbon::parse("{$outDate} {$next->check_time}");

                    if ($out < $in) {
                        $out->addDay();
                    }

                    $totalMinutes += $in->diffInMinutes($out);
                    $firstCheckInTime = $firstCheckInTime ?? $in;
                    $lastCheckOutTime = $out;
                    $i++;
                }
            }

            if ($totalMinutes === 0 || ! $firstCheckInTime || ! $lastCheckOutTime) {
                continue;
            }

            // تخطي الوردية إذا كان هناك سجل تاريخي (period_id = null) يطابق وقت دخول هذه الوردية (HH:MM)
            $firstInHm = substr($firstCheckInTime->toTimeString(), 0, 5);
            if ($dayExistingOvertimes->contains(fn ($ot) => $ot->period_id === null && ! empty($ot->start_time) && substr((string) $ot->start_time, 0, 5) === $firstInHm)) {
                continue;
            }

            [$hours, $minutes] = explode(':', $period->supposed_duration);
            $supposedDurationMinutes = ((int) $hours * 60) + (int) $minutes;

            if ($totalMinutes >= ($supposedDurationMinutes + $allowedOffset)) {
                $overtimeMinutes = $totalMinutes - $supposedDurationMinutes;
                $overtimeHours = round(($overtimeMinutes / 60) * 2) / 2;

                if ($halfHourRule) {
                    $overtimeHours = round($overtimeHours, 2);
                }

                $remainingMinutes = $overtimeMinutes % 60;
                $formattedOvertime = "{$overtimeHours} h {$remainingMinutes} m";

                $results[] = [
                    'employee_id'               => $this->id,
                    'period_id'                 => $period->id,
                    'period_name'               => $period->name,
                    'supposed_duration_minutes' => (int) $overtimeMinutes,
                    'overtime_hours'            => $overtimeHours,
                    'overtime'                  => $formattedOvertime,
                    'overtime_start_time'       => $firstCheckInTime->toTimeString(),
                    'overtime_end_time'         => $lastCheckOutTime->toTimeString(),
                    'check_in_time'             => $firstCheckInTime->toTimeString(),
                    'check_out_time'            => $lastCheckOutTime->toTimeString(),
                ];
            }
        }

        // 5. حساب إضافي البصمات بدون وردية (No Shift) بشكل مستقل إن وجدت ولم يُسجل لها إضافي مسبقاً
        if ($hasNoShift && ! $dayExistingOvertimes->contains(fn ($ot) => $ot->period_id === null)) {
            $noShiftOvertime = $this->calculateNoShiftOvertime($noShiftAttendances, $halfHourRule);
            if (! empty($noShiftOvertime)) {
                $results[] = $noShiftOvertime;
            }
        }

        return $results;
    }

    /**
     * دالة مبسطة لحساب الإضافي للموظفين الذين حضروا بدون وردية (No Shift)
     * جميع ساعات العمل تُعتبر إضافي لأن الساعات المتوقعة هي 0.
     */
    private function calculateNoShiftOvertime(\Illuminate\Support\Collection $attendances, bool $halfHourRule): array
    {
        $totalMinutes = 0;
        $firstCheckInTime = null;
        $lastCheckOutTime = null;

        for ($i = 0; $i < $attendances->count() - 1; $i++) {
            $current = $attendances[$i];
            $next = $attendances[$i + 1];

            if ($current->check_type === 'checkin' && $next->check_type === 'checkout') {
                $inDate = $current->real_check_date ?: $current->check_date;
                $outDate = $next->real_check_date ?: $next->check_date;
                $in  = \Carbon\Carbon::parse("{$inDate} {$current->check_time}");
                $out = \Carbon\Carbon::parse("{$outDate} {$next->check_time}");

                if ($out < $in) {
                    $out->addDay();
                }

                $totalMinutes += $in->diffInMinutes($out);
                $firstCheckInTime = $firstCheckInTime ?? $in;
                $lastCheckOutTime = $out;
                $i++;
            }
        }

        if ($totalMinutes === 0) {
            return [];
        }

        $overtimeHours = round(($totalMinutes / 60) * 2) / 2;

        if ($halfHourRule) {
            $overtimeHours = round($overtimeHours, 2);
        }

        $remainingMinutes = $totalMinutes % 60;
        $formattedOvertime = "{$overtimeHours} h {$remainingMinutes} m";

        return [
            'employee_id'               => $this->id,
            'period_id'                 => null, // لا يوجد وردية
            'period_name'               => 'No Shift',
            'supposed_duration_minutes' => $totalMinutes,
            'overtime_hours'            => $overtimeHours,
            'overtime'                  => $formattedOvertime,
            'overtime_start_time'       => $firstCheckInTime?->toTimeString(),
            'overtime_end_time'         => $lastCheckOutTime?->toTimeString(),
            'check_in_time'             => $firstCheckInTime?->toTimeString(),
            'check_out_time'            => $lastCheckOutTime?->toTimeString(),
        ];
    }
}
