<?php
declare(strict_types=1);
namespace App\Attendance\Services;
use App\Models\Attendance;
final class AttendanceFetcher {
    public function latestFor(int $employeeId): ?Attendance {
        return Attendance::query()->where('employee_id',$employeeId)->latest('id')->first();
    }
    public function hasAcceptedCheckIn(int $employeeId, int $periodId, string $checkDate): bool {
        return Attendance::query()->where('employee_id',$employeeId)->where('period_id',$periodId)->where('check_date',$checkDate)->where('type','CHECKIN')->where('accepted',1)->exists();
    }
}
