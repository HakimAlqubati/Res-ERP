<?php
namespace App\Filament\Clusters\HRAttenanceCluster\Resources\EmployeeOvertimeResource\Pages;

use App\Filament\Clusters\HRAttenanceCluster\Resources\EmployeeOvertimeResource;
use App\Models\EmployeeOvertime;
use App\Services\HR\MonthClosure\MonthClosureService;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\DB;

class CreateEmployeeOvertime extends CreateRecord
{
    protected static string $resource        = EmployeeOvertimeResource::class;
    protected ?bool $hasDatabaseTransactions = true;

    // protected function mutateFormDataBeforeCreate(array $data): array
    // {
    //     // dd($data,$data['type']);
    //     switch ($data['type']) {
    //         case EmployeeOvertime::TYPE_BASED_ON_DAY:
    //             $data = $this->handleOvertimeByDay($data);
    //             break;
    //         case EmployeeOvertime::TYPE_BASED_ON_MONTH:
    //             $data = $this->handleOverTimeMonth($data);
    //             break;

    //         default:
    //             # code...
    //             break;
    //     }
    //     return $data;
    // }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    public function create(bool $another = false): void
    {
        $data = $this->form->getState();
        try {
            app(MonthClosureService::class)->ensureMonthIsOpen($data['date']);
            switch ($data['type']) {
                case EmployeeOvertime::TYPE_BASED_ON_DAY:
                    $create = $this->handleOvertimeByDay($data);
                    if ($create) {
                        showSuccessNotifiMessage('Done');
                        $this->redirect(static::getResource()::getUrl('index'));
                        return;
                    }
                    break;
                case EmployeeOvertime::TYPE_BASED_ON_MONTH:
                    $create = $this->handleOverTimeMonth($data);
                    if ($create) {
                        showSuccessNotifiMessage('Done');
                        $this->redirect(static::getResource()::getUrl('index'));
                        return;
                    }
                    break;

                default:
                    # code...
                    break;
            }

            $this->getRedirectUrl();
        } catch (\Exception $th) {
            showWarningNotifiMessage($th->getMessage());
            // throw $th;
        }
    }

    protected function handleOvertimeByDay(array $data): bool
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
                    'created_by'  => auth()->user()->id,
                    'type'        => EmployeeOvertime::TYPE_BASED_ON_DAY,

                ]);
            }
            DB::commit();
            return true;
        } catch (\Exception $th) {
            DB::rollBack();
            showWarningNotifiMessage('error', $th->getMessage());
            throw $th;
        }
        return false;
    }

    protected function handleOverTimeMonth(array $data): bool
    {
        DB::beginTransaction();
        try {
            $employees = $data['employees_with_month'];

            foreach ($employees as $index => $employee) {
                $attendancesDates = $employee['attendances_dates'];

                foreach ($attendancesDates as $value) {
                    $date       = $value['attendance_date'];
                    $totalHours = $value['total_hours'];
                    $hours      = static::getTotalHours($totalHours);

                    EmployeeOvertime::create([
                        'employee_id' => $employee['employee_id'],
                        'date'        => $date,
                        // 'start_time' => $employee['start_time'],
                        // 'end_time' => $employee['end_time'],
                        'hours'       => $hours,
                        'notes'       => $employee['notes'],
                        'branch_id'   => $data['branch_id'],
                        'created_by'  => auth()->user()->id,
                        'type'        => EmployeeOvertime::TYPE_BASED_ON_MONTH,

                    ]);
                }
            } //code...
            DB::commit();
            return true;
        } catch (\Exception $th) {
            DB::rollBack();
            showWarningNotifiMessage('error', $th->getMessage());
            throw $th;
        }
        return false;
    }

    public static function getTotalHours($timeString)
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
}