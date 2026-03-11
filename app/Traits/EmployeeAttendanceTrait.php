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
        $employeePeriods = $employee ? $employee->periodHistoriesOnDate($date) : collect();

        if ($employee === null || $employeePeriods->isEmpty()) {
            return [];
        }

        // Skip calculation if employee already has an approved overtime for this date
        $hasApprovedOvertime = EmployeeOvertime::where('employee_id', $employee->id)
            ->where('date', $date)
            ->where('approved', 1)
            ->exists();

        if ($hasApprovedOvertime) {
            return [];
        }

        $results = [];
        $arr = [];

        foreach ($employeePeriods as $employeePeriod) {
            $period = $employeePeriod->workPeriod;
            if (!$period) continue;

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

            // dd($attendances, $date, $period, $employee);
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

                $overtimeStartTime = $checkInTime;
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
                    'overtime_start_time'       => $overtimeStartTime->toTimeString(),
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
        // 1. التحقق من وجود إضافي معتمد مسبقاً في هذا اليوم
        if ($this->overtimes->where('date', $date)->isNotEmpty()) {
            return [];
        }

        // 2. فلترة الفترات النشطة لهذا اليوم من الـ Collection
        $activePeriods = $this->periodHistories->filter(function ($history) use ($date) {
            $startValid = is_null($history->start_date) || $history->start_date <= $date;
            $endValid = is_null($history->end_date) || $history->end_date >= $date;
            return $startValid && $endValid;
        });

        if ($activePeriods->isEmpty()) {
            return [];
        }

        // 3. جلب بصمات هذا اليوم فقط (يجب استخدام values لإعادة الفهرسة وتجنب أخطاء الـ Loop)
        $attendances = $this->attendances
            ->where('check_date', $date)
            ->sortBy('id') // 👈 إجبار الترتيب بالتسلسل الصحيح
            ->values();
        if ($attendances->count() < 2) {
            return [];
        }

        $totalMinutes = 0;
        $firstCheckInTime = null;
        $lastCheckOutTime = null;

        // 4. حساب دقائق العمل الفِعلية بدقة (معالجة أزواج الدخول/الخروج)
        for ($i = 0; $i < $attendances->count() - 1; $i++) {
            $current = $attendances[$i];
            $next = $attendances[$i + 1];

            if ($current->check_type === 'checkin' && $next->check_type === 'checkout') {
                $in  = \Carbon\Carbon::parse("{$current->real_check_date} {$current->check_time}");
                $out = \Carbon\Carbon::parse("{$next->real_check_date} {$next->check_time}");

                // معالجة الورديات المسائية التي تعبر لمنتصف الليل (اليوم التالي)
                if ($out < $in) {
                    $out->addDay();
                }

                $totalMinutes += $in->diffInMinutes($out);

                // حفظ أول وقت دخول كبداية للإضافي، وتحديث آخر وقت خروج كنهاية
                $firstCheckInTime = $firstCheckInTime ?? $in;
                $lastCheckOutTime = $out;

                // تخطي بصمة الخروج لأننا أدخلناها في الحساب بنجاح مع الدخول
                $i++;
            }
        }

        if ($totalMinutes === 0) {
            return [];
        }

        // 5. مقارنة وقت العمل الفعلي بوقت الفترة المقررة (Supposed Duration)
        // 5. مقارنة وقت العمل الفعلي بوقت الفترة المقررة (Supposed Duration)
        foreach ($activePeriods as $history) {
            $period = $history->workPeriod;
            if (!$period) continue;

            [$hours, $minutes] = explode(':', $period->supposed_duration);
            $supposedDurationMinutes = ((int)$hours * 60) + (int)$minutes;

            if ($totalMinutes >= ($supposedDurationMinutes + $allowedOffset)) {
                $overtimeMinutes = $totalMinutes - $supposedDurationMinutes;

                // 👈 هنا الحل: استخدام معادلتك الأصلية لإجبار التقريب لأقرب نصف ساعة دائماً
                $overtimeHours = round(($overtimeMinutes / 60) * 2) / 2;

                // تطبيق الشرط الخاص بك (من الكود القديم) إذا لزم الأمر
                if ($halfHourRule) {
                    $overtimeHours = round($overtimeHours, 2);
                }

                // حساب التنسيق النصي بنفس طريقتك الأصلية
                $remainingMinutes = $overtimeMinutes % 60;
                $formattedOvertime = "{$overtimeHours} h {$remainingMinutes} m";

                return [
                    'employee_id'               => $this->id,
                    'period_id'                 => $period->id,
                    'supposed_duration_minutes' => (int) $overtimeMinutes,
                    'overtime_hours'            => $overtimeHours, // 👈 النتيجة الآن ستتطابق
                    'overtime'                  => $formattedOvertime,
                    'overtime_start_time'       => $firstCheckInTime?->toTimeString(),
                    'overtime_end_time'         => $lastCheckOutTime?->toTimeString(),
                ];
            }
        }

        return [];
    }
}
