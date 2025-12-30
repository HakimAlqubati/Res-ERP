<?php

namespace App\Traits;

use App\Models\AppLog;
use App\Models\Attendance;
use App\Models\Employee;
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
        if ($employee === null || $employee->periods->isEmpty()) {
            return [];
        }

        $results = [];
        $arr = [];

        foreach ($employee->periods as $period) {
            $attendances = $this->attendances()
                ->where('employee_id', $employee->id)
                ->where('period_id', $period->id)
                ->where('check_date', $date)
                ->accepted()
                ->orderBy('id')
                ->get();

            $arr[] = $attendances;
            $totalMinutes = 0;
            $checkInTime  = null;
            $checkOutTime = null;

            for ($i = 0; $i < $attendances->count(); $i++) {
                $checkIn = $attendances[$i];

                if ($checkIn->check_type === 'checkin') {
                    $i++;
                    if ($i < $attendances->count()) {
                        $checkOut = $attendances[$i];

                        if ($checkOut->check_type === 'checkout') {
                            $checkInTime  = Carbon::parse("{$checkIn->real_check_date} {$checkIn->check_time}");
                            $checkOutTime = Carbon::parse("{$checkOut->real_check_date} {$checkOut->check_time}");

                            if ($checkOutTime < $checkInTime) {
                                $checkOutTime->addDay();
                            }

                            $totalMinutes += $checkInTime->diffInMinutes($checkOutTime);
                        }
                    }
                }
            }

            $arr[] = $attendances;
            $arr2[] = $totalMinutes;

            list($hours, $minutes) = explode(':', $period->supposed_duration);
            $supposedDurationMinutes = ($hours * 60) + $minutes;

            if ($totalMinutes >= ($supposedDurationMinutes + Attendance::getMinutesByConstant(Setting::getSetting('period_allowed_to_calculate_overtime')))) {
                $overtimeMinutes = $totalMinutes - $supposedDurationMinutes;
                $overtimeHours    = round($overtimeMinutes / 60 * 2) / 2;
                $remainingMinutes = $overtimeMinutes % 60;

                $formattedOvertime = "{$overtimeHours} h {$remainingMinutes} m";

                $overtimeStartTime = $period->end_at;
                $overtimeEndTime   = $checkOutTime;

                if (
                    Setting::getSetting('period_allowed_to_calculate_overtime') == Attendance::PERIOD_ALLOWED_OVERTIME_HOUR
                    && Setting::getSetting('calculating_overtime_with_half_hour_after_hour')
                ) {
                    $overtimeHours = round($overtimeHours, 2);
                }

                $results[] = [
                    'employee_id'               => $employee->id,
                    'period_id'                 => $period->id,
                    'supposed_duration_minutes' => (int) $overtimeMinutes,
                    'overtime_hours'            => $overtimeHours,
                    'overtime'                  => $formattedOvertime,
                    'overtime_start_time'       => $overtimeStartTime,
                    'overtime_end_time'         => $overtimeEndTime->toTimeString(),
                    'check_in_time'             => $checkIn->check_time,
                    'check_out_time'            => $checkOut->check_time,
                ];
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

        AppLog::write(
            "[periodsOnDate DEBUG] Employee ID: {$this->id}, Employee DB: {$employeeConnection}, Period Query DB: {$periodConnection}, All EmployeePeriods: {$allPeriods}, All PeriodDays: {$allDays}, DayOfWeek: {$dow}",
            AppLog::LEVEL_INFO,
            'attendance'
        );

        $workPeriods = $this->employeePeriods()
            ->with(['workPeriod', 'days'])
            ->where(function ($q) use ($d) {
                $q->whereNull('start_date')->orWhere('start_date', '<=', $d->toDateString());
            })
            ->where(function ($q) use ($d) {
                $q->whereNull('end_date')->orWhere('end_date', '>=', $d->toDateString());
            })
            ->whereHas('days', function ($q) use ($dow, $d) {
                // Filter by day_of_week if needed
            })
            ->get();

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
}
