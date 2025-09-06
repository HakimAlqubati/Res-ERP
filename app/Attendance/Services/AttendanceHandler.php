<?php
declare(strict_types=1);
namespace App\Attendance\Services;
use App\Attendance\DTO\AttendanceDTO;
use App\Attendance\DTO\AttendanceResult;
use App\Attendance\Domain\BoundsCalculator;
use App\Attendance\Config\AttendanceConfig;
use App\Models\Attendance;
use App\Models\WorkPeriod;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
final class AttendanceHandler {
    public function __construct(
        private AttendanceConfig $config,
        private BoundsCalculator $boundsCalc,
        private TimeWindowService $timeWin,
        private AttendanceValidator $validator,
        private AttendanceFetcher $fetcher,
        private AttendanceSaver $saver,
        private CheckTypeDecider $decider,
        private CheckInService $checkIn,
        private CheckOutService $checkOut
    ) {}
    public function handle(int $employeeId, int $periodId, string $deviceId, CarbonImmutable $now, bool $isRequest=false): AttendanceResult {
       dd('sdf');
        $period = WorkPeriod::find($periodId); if (!$period) return AttendanceResult::fail('Work period not found.');
        $bounds = $this->boundsCalc->compute($period, $now);
        $checkDate = $bounds->periodStart->format('Y-m-d');
        $realCheckDate = $now->format('Y-m-d');
        if ($res = $this->validator->ensureMonthIsOpen($checkDate)) return $res;
        if (!$this->timeWin->isWithinWindow($now, $bounds)) {
            $meta=['employee_id'=>$employeeId,'period_id'=>$periodId,'real_check_date'=>$realCheckDate,'check_time'=>$now->format('H:i:s'),'device_id'=>$deviceId];
            $this->saver->storeRejected('Out of allowed window.', $meta);
            return AttendanceResult::fail('Out of allowed window.');
        }
        if ($res = $this->validator->guardRateLimit($employeeId, $now, $isRequest)) {
            $meta=['employee_id'=>$employeeId,'period_id'=>$periodId,'real_check_date'=>$realCheckDate,'check_time'=>$now->format('H:i:s'),'device_id'=>$deviceId];
            $this->saver->storeRejected($res->message ?? 'Rate limited.', $meta);
            return $res;
        }
        $type = $this->decider->decide($employeeId,$periodId,$now,$bounds,$checkDate);
        $dto = match($type){
            'CHECKIN' => $this->checkIn->buildDTO($employeeId,$periodId,$deviceId,$now,$bounds,$checkDate,$realCheckDate),
            'CHECKOUT'=> $this->checkOut->buildDTO($employeeId,$periodId,$deviceId,$now,$bounds,$checkDate,$realCheckDate),
            default => throw new InvalidArgumentException("Unknown type: $type"),
        };
        $att = DB::transaction(function() use ($dto){
            if ($dto->type==='CHECKIN'){
                $exists = Attendance::query()->where('employee_id',$dto->employeeId)->where('period_id',$dto->periodId)->where('check_date',$dto->checkDate)->where('type','CHECKIN')->where('accepted',1)->lockForUpdate()->exists();
                if ($exists) return null;
            }
            return $this->saver->storeAccepted($dto);
        });
        if ($att===null && $dto->type==='CHECKIN'){
            $dtoOut = new AttendanceDTO($dto->employeeId,$dto->periodId,$dto->checkDate,$dto->realCheckDate,$dto->checkTime,'CHECKOUT','ON_TIME',0,0,0,0,$dto->fromPreviousDay,$dto->deviceId);
            $att = DB::transaction(fn()=> $this->saver->storeAccepted($dtoOut));
            return AttendanceResult::ok(null,'Auto-switched to CHECKOUT (already checked-in).');
        }
        return AttendanceResult::ok(null,'Attendance recorded.');
    }
}
