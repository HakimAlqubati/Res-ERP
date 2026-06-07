<?php

namespace App\Modules\HR\AttendanceReports\Processors;

use App\Models\EmployeePeriodHistory;
use App\Models\WorkPeriod;
use Illuminate\Support\Collection;

/**
 * Class VirtualPeriodInjector
 * 
 * Implements the Null Object Pattern to gracefully handle ad-hoc (No-Shift) 
 * attendance records. It isolates the mapping logic, ensuring the core 
 * reporting domain remains agnostic of edge cases and adheres strictly to the SRP.
 */
class VirtualPeriodInjector
{
    public const VIRTUAL_PERIOD_ID = -1;

    /**
     * Evaluates daily attendances and injects a virtual period pipeline if unassigned records exist.
     * 
     * @param Collection $dayAttendances
     * @param Collection $dayHistories
     * @param Collection $workPeriodMap
     * @return void
     */
    public static function inject(
        Collection $dayAttendances,
        Collection $dayHistories,
        Collection $workPeriodMap
    ): void {
        // Laravel's groupBy resolves null keys to an empty string ("")
        if (!$dayAttendances->has("")) {
            return;
        }

        self::remapAttendances($dayAttendances);
        self::ensureVirtualPeriodExists($workPeriodMap);
        self::injectVirtualHistory($dayHistories);
    }

    /**
     * Migrates unassigned attendances into the virtual period grouping.
     */
    private static function remapAttendances(Collection $dayAttendances): void
    {
        $dayAttendances->put(self::VIRTUAL_PERIOD_ID, $dayAttendances->get(""));
        $dayAttendances->forget("");
    }

    /**
     * Lazily instantiates and registers the virtual WorkPeriod within the global mapping array.
     */
    private static function ensureVirtualPeriodExists(Collection $workPeriodMap): void
    {
        if ($workPeriodMap->has(self::VIRTUAL_PERIOD_ID)) {
            return;
        }

        $virtualPeriod = new WorkPeriod();
        $virtualPeriod->id = self::VIRTUAL_PERIOD_ID;
        $virtualPeriod->name = 'No Shift';
        $virtualPeriod->start_at = '00:00:00';
        $virtualPeriod->end_at = '00:00:00';
        $virtualPeriod->day_and_night = 0;
        $virtualPeriod->supposed_duration_hourly = '00:00';
        $virtualPeriod->supposed_duration = '00:00';

        $workPeriodMap->put(self::VIRTUAL_PERIOD_ID, $virtualPeriod);
    }

    /**
     * Appends a virtual execution timeline matching the processor's iteration contract.
     */
    private static function injectVirtualHistory(Collection $dayHistories): void
    {
        $virtualHistory = new EmployeePeriodHistory();
        $virtualHistory->period_id = self::VIRTUAL_PERIOD_ID;
        $virtualHistory->start_time = '00:00:00';
        $virtualHistory->end_time = '00:00:00';
        $virtualHistory->branch_id = null;

        $dayHistories->push($virtualHistory);
    }
}
