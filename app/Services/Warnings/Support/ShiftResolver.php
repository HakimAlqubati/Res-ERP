<?php

namespace App\Services\Warnings\Support;

use App\Models\Employee;
use App\Models\WorkPeriod;
use Carbon\Carbon;

final class ShiftResolver
{
    /**
     * @return array<int,array{
     *    period: WorkPeriod,
     *    start: Carbon, end: Carbon,
     *    grace_deadline: Carbon
     * }>
     */
    public function resolve(Employee $emp, Carbon $date, int $graceMinutes = 15): array
    {
        $out = [];

        // DEBUG: تسجيل قاعدة البيانات المستخدمة
        $db = config('database.connections.mysql.database');
        $periods = $emp->periodsOnDate($date);

        \App\Models\AppLog::write(
            "[ShiftResolver] DB: {$db}, Employee ID: {$emp->id}, Date: {$date->toDateString()}, Periods count: {$periods->count()}",
            \App\Models\AppLog::LEVEL_INFO,
            'attendance'
        );

        foreach ($periods as $empPeriod) {
            /** @var WorkPeriod $p */
            $p = $empPeriod->workPeriod;

            // نفترض الأعمدة: start_at, end_at بنمط "HH:MM:SS"
            $start = Carbon::parse($date->toDateString() . ' ' . $p->start_at);
            $end   = Carbon::parse($date->toDateString() . ' ' . $p->end_at);

            // اذا الشفت يعبر منتصف الليل
            if ($end->lessThanOrEqualTo($start)) {
                $end->addDay();
            }

            $grace = $start->copy()->addMinutes($graceMinutes);

            $out[] = [
                'period'         => $p,
                'start'          => $start,
                'end'            => $end,
                'grace_deadline' => $grace,
            ];
        }

        return $out;
    }
}
