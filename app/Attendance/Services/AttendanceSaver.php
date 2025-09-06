<?php
declare(strict_types=1);
namespace App\Attendance\Services;
use App\Attendance\DTO\AttendanceDTO;
use App\Models\Attendance;
final class AttendanceSaver {
    public function storeAccepted(AttendanceDTO $d): Attendance {
        return Attendance::create([
            'employee_id'=>$d->employeeId,'period_id'=>$d->periodId,'check_date'=>$d->checkDate,'real_check_date'=>$d->realCheckDate,
            'check_time'=>$d->checkTime,'type'=>$d->type,'status'=>$d->status,'delay_minutes'=>$d->delayMinutes,
            'early_arrival_min'=>$d->earlyArrivalMinutes,'early_depart_min'=>$d->earlyDepartureMinutes,'late_depart_min'=>$d->lateDepartureMinutes,
            'from_previous_day'=>$d->fromPreviousDay?1:0,'accepted'=>1,'device_id'=>$d->deviceId,
        ]);
    }
    public function storeRejected(string $message, array $meta): void {
        Attendance::create([
            'employee_id'=>$meta['employee_id']??null,'period_id'=>$meta['period_id']??null,
            'check_date'=>$meta['check_date']??($meta['real_check_date']??null),'real_check_date'=>$meta['real_check_date']??null,
            'check_time'=>$meta['check_time']??null,'type'=>$meta['type']??null,'status'=>'REJECTED',
            'notes'=>$message,'accepted'=>0,'device_id'=>$meta['device_id']??null,
        ]);
    }
}
