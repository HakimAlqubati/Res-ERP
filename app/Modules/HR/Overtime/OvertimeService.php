<?php

namespace App\Modules\HR\Overtime;

use App\Models\EmployeeOvertime;
use Exception;
use Illuminate\Support\Facades\DB;

class OvertimeService
{
    /**
     * Handle overtime creation based on day
     *
     * @param array $data
     * @return bool
     * @throws Exception
     */
    public function handleOvertimeByDay(array $data): bool
    {
        DB::beginTransaction();
        try {
            $employees = $data['employees'];
            foreach ($employees as $index => $employee) {
                EmployeeOvertime::create([
                    'employee_id' => $employee['employee_id'],
                    'date'        => $data['date'],
                    'start_time'  => $employee['start_time'],
                    'end_time'    => $employee['end_time'],
                    'hours'       => $employee['hours'],
                    'notes'       => $employee['notes'],
                    'branch_id'   => $data['branch_id'],
                    'created_by'  => auth()->id(),
                    'type'        => EmployeeOvertime::TYPE_BASED_ON_DAY,

                ]);
            }
            DB::commit();
            return true;
        } catch (Exception $th) {
            DB::rollBack();
            // We rethrow the exception so the controller can handle it (show notification etc)
            throw $th;
        }
    }

    /**
     * Handle overtime creation based on month
     *
     * @param array $data
     * @return bool
     * @throws Exception
     */
    public function handleOverTimeMonth(array $data): bool
    {
        DB::beginTransaction();
        try {
            $employees = $data['employees_with_month'];

            foreach ($employees as $index => $employee) {
                $attendancesDates = $employee['attendances_dates'];

                foreach ($attendancesDates as $value) {
                    $date       = $value['attendance_date'];
                    $totalHours = $value['total_hours'];
                    $hours      = $this->getTotalHours($totalHours);

                    EmployeeOvertime::create([
                        'employee_id' => $employee['employee_id'],
                        'date'        => $date,
                        // 'start_time' => $employee['start_time'],
                        // 'end_time' => $employee['end_time'],
                        'hours'       => $hours,
                        'notes'       => $employee['notes'],
                        'branch_id'   => $data['branch_id'],
                        'created_by'  => auth()->id(),
                        'type'        => EmployeeOvertime::TYPE_BASED_ON_MONTH,

                    ]);
                }
            }
            DB::commit();
            return true;
        } catch (Exception $th) {
            DB::rollBack();
            throw $th;
        }
    }

    /**
     * Calculate total hours from time string
     *
     * @param string $timeString
     * @return float
     */
    public function getTotalHours($timeString)
    {
        // Extract hours and minutes using regular expressions
        preg_match('/(\d+)\s*h/', $timeString, $hoursMatch);
        preg_match('/(\d+)\s*m/', $timeString, $minutesMatch);

        $hours   = isset($hoursMatch[1]) ? (int) $hoursMatch[1] : 0;
        $minutes = isset($minutesMatch[1]) ? (int) $minutesMatch[1] : 0;

        // Convert minutes to hours and add to the total hours
        $totalHours = $hours + ($minutes / 60);

        return $totalHours;
    }

    /**
     * Approve overtime
     *
     * @param int $id
     * @return EmployeeOvertime
     * @throws Exception
     */
    /**
     * Approve overtime
     *
     * @param array|int $ids
     * @return \Illuminate\Database\Eloquent\Collection
     * @throws Exception
     */
    public function approve(array|int $ids)
    {
        $ids = is_array($ids) ? $ids : [$ids];

        $overtimes = EmployeeOvertime::whereIn('id', $ids)->get();

        foreach ($overtimes as $overtime) {
            if (!$overtime->approved) {
                $overtime->update([
                    'approved'    => 1,
                    'approved_by' => auth()->id(),
                    'approved_at' => now(),
                ]);
            }
        }

        return $overtimes;
    }

    /**
     * Undo overtime approval
     *
     * @param array|int $ids
     * @return \Illuminate\Database\Eloquent\Collection
     * @throws Exception
     */
    public function undoApproval(array|int $ids)
    {
        $ids = is_array($ids) ? $ids : [$ids];

        $overtimes = EmployeeOvertime::whereIn('id', $ids)->get();

        foreach ($overtimes as $overtime) {
            if ($overtime->approved) {
                $overtime->update([
                    'approved'    => 0,
                    'approved_by' => null,
                    'approved_at' => null,
                ]);
            }
        }

        return $overtimes;
    }

    /**
     * Get overtime records
     *
     * @param array $filters
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function getOvertime(array $filters = [])
    {
        $query = EmployeeOvertime::query()
            ->with(['employee:id,name', 'approvedBy:id,name', 'createdBy:id,name']);

        if (isset($filters['employee_id'])) {
            $query->where('employee_id', $filters['employee_id']);
        }

        if (isset($filters['date_from'])) {
            $query->whereDate('date', '>=', $filters['date_from']);
        }

        if (isset($filters['date_to'])) {
            $query->whereDate('date', '<=', $filters['date_to']);
        }

        if (isset($filters['approved'])) {
            $query->where('approved', filter_var($filters['approved'], FILTER_VALIDATE_BOOLEAN));
        }

        if (isset($filters['branch_id'])) {
            $query->where('branch_id', $filters['branch_id']);
        }

        return $query->latest('id')->paginate($filters['per_page'] ?? 15);
    }
}
