<?php

namespace App\Modules\HR\Attendance\Contracts;

use App\Models\Employee;
use App\Modules\HR\Attendance\DTOs\ShiftInfoDTO;
use Carbon\Carbon;

/**
 * واجهة تحديد الوردية المناسبة
 */
interface ShiftResolverInterface
{
    /**
     * تحديد الوردية المناسبة للموظف في وقت معين
     * 
     * @param Employee $employee الموظف
     * @param Carbon $time الوقت المطلوب
     * @return ShiftInfoDTO|null معلومات الوردية أو null إذا لم توجد
     */
    public function resolve(Employee $employee, Carbon $time): ?ShiftInfoDTO;

    /**
     * حساب حدود الوردية
     * 
     * @param \App\Models\WorkPeriod $period الوردية
     * @param string $shiftDate تاريخ الوردية
     * @return array حدود الوردية (start, end, windowStart, windowEnd)
     */
    public function calculateBounds(\App\Models\WorkPeriod $period, string $shiftDate): array;
}
