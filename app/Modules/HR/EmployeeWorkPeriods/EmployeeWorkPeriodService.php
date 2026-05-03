<?php

namespace App\Modules\HR\EmployeeWorkPeriods;

use App\Models\Attendance;
use App\Models\EmployeePeriod;
use App\Models\EmployeePeriodHistory;
use App\Models\WorkPeriod;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\DB;

class EmployeeWorkPeriodService
{
    /**
     * Assign work periods to an employee.
     *
     * @param \App\Models\Employee $employee
     * @param array $data Expected keys: 'periods' (array of IDs), 'start_date', 'end_date' (optional), 'period_days' (array)
     * @return bool
     * @throws Exception
     */
    public function assignPeriodsToEmployee($employee, array $data): bool
    {
        DB::beginTransaction();
        try {
            $selectedPeriodsWithDates = [];
            foreach ($data['periods'] as $periodId) {
                $selectedPeriodsWithDates[] = [
                    'period_id'  => $periodId,
                    'start_date' => $data['start_date'],
                    'end_date'   => $data['end_date'] ?? null,
                ];
            }

            if ($this->isInternalPeriodsOverlappingWithDates($selectedPeriodsWithDates)) {
                throw new Exception('There are overlapping shifts with overlapping periods and times. Please check your selection.');
            }

            $dataPeriods = array_map('intval', $data['periods']);

            // Insert new periods into hr_employee_periods table
            foreach ($dataPeriods as $value) {
                $workPeriod    = WorkPeriod::find($value);
                $periodStartAt = $workPeriod?->start_at;
                $periodEndAt   = $workPeriod?->end_at;

                // أيام الفترة المراد إدخالها
                $periodDays = $data['period_days'] ?? [];

                if ($this->isOverlappingDays_(
                    $employee->id,
                    $periodDays,
                    $periodStartAt,
                    $periodEndAt,
                    $data['start_date'],
                    $data['end_date'] ?? null,
                )) {
                    throw new Exception('❌ Cannot add this Work Period as it overlaps with an existing period.');
                }

                $employeePeriod              = new EmployeePeriod();
                $employeePeriod->employee_id = $employee->id;
                $employeePeriod->period_id   = $value;
                $employeePeriod->start_date  = $data['start_date'];
                $employeePeriod->end_date    = $data['end_date'] ?? null;
                $employeePeriod->save();

                foreach ($data['period_days'] as $dayOfWeek) {
                    $employeePeriod->days()->create([
                        'day_of_week' => $dayOfWeek,
                    ]);

                    EmployeePeriodHistory::create([
                        'employee_id' => $employee->id,
                        'period_id'   => $value,
                        'start_date'  => $data['start_date'],
                        'end_date'    => $data['end_date'] ?? null,
                        'start_time'  => $periodStartAt,
                        'end_time'    => $periodEndAt,
                        'day_of_week' => $dayOfWeek,
                        'branch_id' => $employee?->branch_id,
                    ]);
                }
            }

            DB::commit();
            return true;
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Assign additional days to an existing EmployeePeriod.
     *
     * @param EmployeePeriod $employeePeriod
     * @param array $days Array of day_of_week values to add
     * @return void
     * @throws Exception
     */
    public function assignDaysToEmployeePeriod(EmployeePeriod $employeePeriod, array $days): void
    {
        DB::beginTransaction();
        try {
            foreach ($days as $day) {
                $employeePeriod->days()->create([
                    'day_of_week' => $day,
                ]);

                EmployeePeriodHistory::create([
                    'employee_id' => $employeePeriod->employee_id,
                    'period_id'   => $employeePeriod->period_id,
                    'start_date'  => $employeePeriod->start_date,
                    'end_date'    => $employeePeriod->end_date,
                    'start_time'  => $employeePeriod->workPeriod->start_at,
                    'end_time'    => $employeePeriod->workPeriod->end_at,
                    'day_of_week' => $day,
                    'branch_id' => $employeePeriod?->employee?->branch_id,
                ]);
            }
            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * End/Delete an employee period.
     *
     * @param int $employeePeriodId
     * @param string $endDate
     * @return void
     * @throws Exception
     */
    public function endEmployeePeriod(int $employeePeriodId, string $endDate): void
    {
        DB::beginTransaction();
        try {
            $period = EmployeePeriod::findOrFail($employeePeriodId);

            // حذف كل الأيام المرتبطة بهذه الفترة
            $period->days()->delete();

            // تحديث الهستوري
            EmployeePeriodHistory::where('employee_id', $period->employee_id)
                ->where('period_id', $period->period_id)
                ->where('start_date', $period->start_date)
                ->update(['end_date' => $endDate]);

            // حذف الفترة
            $period->delete();

            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Get employee periods.
     *
     * @param int $employeeId
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getEmployeePeriods(int $employeeId)
    {
        return EmployeePeriod::with(['workPeriod', 'days'])
            ->where('employee_id', $employeeId)
            ->orderBy('id', 'desc')
            ->get();
    }

    private function isInternalPeriodsOverlappingWithDates($selectedPeriodsWithDates)
    {
        $periods = WorkPeriod::whereIn('id', array_column($selectedPeriodsWithDates, 'period_id'))
            ->get()
            ->keyBy('id');

        $count = count($selectedPeriodsWithDates);
        for ($i = 0; $i < $count; $i++) {
            for ($j = $i + 1; $j < $count; $j++) {
                $a = $selectedPeriodsWithDates[$i];
                $b = $selectedPeriodsWithDates[$j];

                $periodA = $periods[$a['period_id']];
                $periodB = $periods[$b['period_id']];

                // ✅ استخدم Carbon وحسب day_and_night
                $aStart = Carbon::createFromFormat('H:i:s', $periodA->start_at);
                $aEnd   = Carbon::createFromFormat('H:i:s', $periodA->end_at);
                if ($periodA->day_and_night) {
                    $aEnd->addDay();
                }

                $bStart = Carbon::createFromFormat('H:i:s', $periodB->start_at);
                $bEnd   = Carbon::createFromFormat('H:i:s', $periodB->end_at);
                if ($periodB->day_and_night) {
                    $bEnd->addDay();
                }

                // تحقق من التداخل
                $timesOverlap = ($aStart <= $bEnd) && ($bStart <= $aEnd);

                // تحقق من التواريخ
                $aEndDate = $a['end_date'] ?? null;
                $bEndDate = $b['end_date'] ?? null;
                $datesOverlap =
                    ($aEndDate === null || $b['start_date'] <= $aEndDate) &&
                    ($bEndDate === null || $a['start_date'] <= $bEndDate);

                if ($timesOverlap && $datesOverlap) {
                    return true;
                }
            }
        }
        return false;
    }

    private function isOverlappingDays_(
        $employeeId,
        $periodDays,
        $periodStartAt,
        $periodEndAt,
        $periodStartDate,
        $periodEndDate = null,
        $excludePeriodId = null // Note: This would exclude WorkPeriod ID if used, as History doesn't store EmployeePeriod ID
    ) {
        $query = EmployeePeriodHistory::query()
            ->with('workPeriod')
            ->where('employee_id', $employeeId)
            ->whereIn('day_of_week', $periodDays);

        $query->where(function ($q) use ($periodStartDate, $periodEndDate) {
            $q->whereNull('end_date')->orWhere(function ($q2) use ($periodStartDate, $periodEndDate) {
                if ($periodEndDate) {
                    $q2->where('start_date', '<=', $periodEndDate)
                        ->where(function ($q3) use ($periodStartDate) {
                            $q3->whereNull('end_date')->orWhere('end_date', '>=', $periodStartDate);
                        });
                } else {
                    $q2->where('end_date', '>=', $periodStartDate)->orWhereNull('end_date');
                }
            });
        });

        if ($excludePeriodId) {
            $query->where('period_id', '!=', $excludePeriodId);
        }

        $overlappingHistories = $query->get();

        $currentStart = Carbon::createFromFormat('H:i:s', $periodStartAt);
        $currentEnd   = Carbon::createFromFormat('H:i:s', $periodEndAt);

        $currentWorkPeriodModel = WorkPeriod::where('start_at', $periodStartAt)
            ->where('end_at', $periodEndAt)->first();

        if ($currentWorkPeriodModel?->day_and_night) {
            $currentEnd->addDay();
        }

        foreach ($overlappingHistories as $history) {
            $existStart = Carbon::createFromFormat('H:i:s', $history->start_time);
            $existEnd   = Carbon::createFromFormat('H:i:s', $history->end_time);

            $wp = $history->workPeriod;
            if ($wp && $wp->day_and_night) {
                $existEnd->addDay();
            }

            $timesOverlap = ($currentStart <= $existEnd) && ($existStart <= $currentEnd);

            if ($timesOverlap) {
                return true;
            }
        }
        return false;
    }
}
