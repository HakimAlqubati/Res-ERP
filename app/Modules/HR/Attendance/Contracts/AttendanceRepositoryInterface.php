<?php

namespace App\Modules\HR\Attendance\Contracts;

use App\Models\Attendance;
use App\Models\Employee;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * واجهة Repository للحضور
 * 
 * تعريف جميع عمليات الوصول للبيانات المتعلقة بالحضور
 */
interface AttendanceRepositoryInterface
{
    /**
     * إنشاء سجل حضور جديد
     */
    public function create(array $data): Attendance;

    /**
     * إنشاء سجل حضور مرفوض
     */
    public function createRejected(
        Employee $employee,
        Carbon $time,
        string $message,
        int $periodId,
        string $attendanceType
    ): void;

    /**
     * البحث عن سجل دخول مفتوح (بدون خروج)
     */
    public function findOpenCheckIn(int $employeeId, int $periodId, string $date): ?Attendance;

    /**
     * جلب سجلات الحضور اليومية للموظف
     */
    public function getDailyRecords(int $employeeId, string $date): Collection;

    /**
     * جلب سجلات الحضور للموظف في وردية معينة
     */
    public function getShiftRecords(int $employeeId, int $periodId, string $date): Collection;

    /**
     * جلب سجلات الخروج لليوم
     */
    public function getCheckoutsForDay(
        int $employeeId,
        int $periodId,
        string $date,
        ?int $exceptId = null
    ): Collection;

    /**
     * البحث عن سجل حضور بالمعرف
     */
    public function find(int $id): ?Attendance;

    /**
     * تحديث سجل حضور
     */
    public function update(Attendance $attendance, array $data): Attendance;
}
