<?php

namespace App\Modules\HR\Attendance\Repositories;

use App\Models\Attendance;
use App\Models\Employee;
use App\Modules\HR\Attendance\Contracts\AttendanceRepositoryInterface;
use App\Modules\HR\Attendance\Enums\CheckType;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * تنفيذ Repository للحضور
 * 
 * يتعامل مع جميع عمليات الوصول لقاعدة البيانات المتعلقة بالحضور
 */
class AttendanceRepository implements AttendanceRepositoryInterface
{
    /**
     * إنشاء سجل حضور جديد
     */
    public function create(array $data): Attendance
    {
        return Attendance::create($data);
    }

    /**
     * إنشاء سجل حضور مرفوض
     */
    public function createRejected(
        Employee $employee,
        Carbon $time,
        string $message,
        int $periodId,
        string $attendanceType
    ): void {
        try {
            Attendance::storeNotAccepted(
                $employee,
                $time->toDateString(),
                $time->toTimeString(),
                $time->format('l'),
                $message,
                $periodId,
                $attendanceType
            );
        } catch (\Throwable) {
            // Silent fail - لا نريد أن يفشل النظام بسبب تسجيل السجل المرفوض
        }
    }

    /**
     * البحث عن سجل دخول مفتوح (بدون خروج)
     */
    public function findOpenCheckIn(int $employeeId, int $periodId, string $date): ?Attendance
    {
        return Attendance::query()
            ->where('employee_id', $employeeId)
            ->where('period_id', $periodId)
            ->where('check_date', $date)
            ->where('check_type', CheckType::CHECKIN->value)
            ->where('accepted', 1)
            ->whereDoesntHave('checkout')
            ->latest('id')
            ->first();
    }

    /**
     * جلب سجلات الحضور اليومية للموظف
     */
    public function getDailyRecords(int $employeeId, string $date): Collection
    {
        return Attendance::with('period')
            ->where('employee_id', $employeeId)
            ->where('check_date', $date)
            ->where('accepted', 1)
            ->get();
    }

    /**
     * جلب سجلات الحضور للموظف في وردية معينة
     */
    public function getShiftRecords(int $employeeId, int $periodId, string $date): Collection
    {
        return Attendance::with('period')
            ->where('employee_id', $employeeId)
            ->where('period_id', $periodId)
            ->where('check_date', $date)
            ->where('accepted', 1)
            ->get();
    }

    /**
     * جلب سجلات الخروج لليوم
     */
    public function getCheckoutsForDay(
        int $employeeId,
        int $periodId,
        string $date,
        ?int $exceptId = null
    ): Collection {
        return Attendance::query()
            ->where('employee_id', $employeeId)
            ->where('period_id', $periodId)
            ->where('check_date', $date)
            ->where('check_type', CheckType::CHECKOUT->value)
            ->where('accepted', 1)
            ->when($exceptId, fn($q) => $q->where('id', '!=', $exceptId))
            ->get();
    }

    /**
     * البحث عن سجل حضور بالمعرف
     */
    public function find(int $id): ?Attendance
    {
        return Attendance::find($id);
    }

    /**
     * تحديث سجل حضور
     */
    public function update(Attendance $attendance, array $data): Attendance
    {
        $attendance->update($data);
        return $attendance->fresh();
    }

    /**
     * جلب سجل الدخول للخروج المحدد
     */
    public function getCheckInForCheckout(int $checkInRecordId): ?Attendance
    {
        return Attendance::query()
            ->where('id', $checkInRecordId)
            ->where('check_type', CheckType::CHECKIN->value)
            ->where('accepted', 1)
            ->first();
    }

    /**
     * جلب أحدث سجل دخول للموظف في وردية معينة
     */
    public function getLatestCheckIn(int $employeeId, int $periodId, string $date): ?Attendance
    {
        return Attendance::query()
            ->where('employee_id', $employeeId)
            ->where('period_id', $periodId)
            ->where('check_date', $date)
            ->where('check_type', CheckType::CHECKIN->value)
            ->where('accepted', 1)
            ->latest('id')
            ->first();
    }

    /**
     * جلب أحدث سجل خروج للموظف في وردية معينة
     */
    public function getLatestCheckout(int $employeeId, int $periodId, string $date): ?Attendance
    {
        return Attendance::query()
            ->where('employee_id', $employeeId)
            ->where('period_id', $periodId)
            ->where('check_date', $date)
            ->where('check_type', CheckType::CHECKOUT->value)
            ->where('accepted', 1)
            ->latest('check_time')
            ->first();
    }
}
