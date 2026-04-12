<?php

namespace App\Filament\Clusters\HRAttendanceReport\Resources;

use App\Filament\Clusters\HRAttendanceReport\Resources\EmployeesAttednaceReportResource;
use App\Models\Employee;
use App\Modules\HR\AttendanceReports\Contracts\AttendanceReportInterface;
use App\Models\EmployeeBranchLog;
use Carbon\Carbon;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Model;

class ListEmployeesAttednaceReport extends ListRecords
{
    protected static string $resource = EmployeesAttednaceReportResource::class;
    protected   string $view     = 'filament.pages.hr-reports.attendance.pages.attendance-employees-with-header-fixed-new';

    public $showDetailsModal = false;
    public $modalData        = [];
    /**
     * @param  Model|array  $record
     */
    public function getTableRecordKey(Model|array $record): string
    {
        if (is_array($record)) {
            // لو البيانات جاية كمصفوفة
            return (string) ($record['employee_id'] ?? $record['id'] ?? '');
        }

        // لو Model
        $attributes = $record->getAttributes();

        return (string) ($attributes['employee_id'] ?? $record->getKey());
    }

    private function formatDuration($totalMinutes)
    {
        $hours   = intdiv($totalMinutes, 60);
        $minutes = $totalMinutes % 60;
        return "{$hours} h {$minutes} m";
    }

    public function getViewData(): array
    {
        $branch_id = $this->getTable()->getFilters()['branch_id']->getState()['value'];
        $date      = $this->getTable()->getFilters()['filter_date']->getState()['date'];

        $report_data = [];

        $employeesPaginator = [];
        $employeeIds        = [];

        // If no branch is selected, return empty data
        if (empty($branch_id) || $branch_id == '') {
            return [
                'employees'     => [],
                'report_data'   => [],
                'branch_id'     => null,
                'date'          => $date,
                'totalSupposed' => $this->formatDuration(0),
                'totalWorked'   => $this->formatDuration(0),
                'totalApproved' => $this->formatDuration(0),
            ];
        }

        $dateCarbon = Carbon::parse($date);
        $employeeIdsInBranch = EmployeeBranchLog::getEmployeesForBranchInRange($branch_id, $dateCarbon, $dateCarbon);

        $employeesPaginator = Employee::whereIn('id', $employeeIdsInBranch)
            ->active()
            ->select('id', 'name')
            ->paginate(100);
        $employeeIds = $employeesPaginator->pluck('id')->toArray();

        $service = app(AttendanceReportInterface::class);
        $reports = $service->getEmployeesDateReport($employeeIds, $date);

        // بعد جلب التقارير:
        $employees = $reports->map(function ($item) {
            // تحويل attendance_report إلى مصفوفة (لأنها Collection)
            $attendance_report = $item['attendance_report']->map(function ($dayData) {
                if (!is_array($dayData)) {
                    return []; // أو يمكنك تسجيل خطأ أو تجاهله حسب الحاجة
                }

                $dayData['periods'] = isset($dayData['periods']) && $dayData['periods'] instanceof \Illuminate\Support\Collection
                    ? $dayData['periods']->toArray()
                    : (is_array($dayData['periods'] ?? null) ? $dayData['periods'] : []);

                return $dayData;
            })->toArray();

            return [
                'employee'          => $item['employee'],
                'attendance_report' => $attendance_report,
            ];
        })->values()->toArray();

        // Calculate totals
        $totalSupposed = 0;
        $totalWorked   = 0;
        $totalApproved = 0;

        return [
            'employees'   => $employees,
            'report_data'   => $report_data,
            'branch_id'     => $branch_id,
            'date'          => $date,
            // 'totalSupposed' => $totalSupposed,
            'totalSupposed' => $this->formatDuration($totalSupposed),
            'totalWorked'   => $this->formatDuration($totalWorked),
            'totalApproved' => $this->formatDuration($totalApproved),
        ];
    }



    // Add a method to handle showing the modal with data

    public function showDetails($date, $employeeId, $periodId)
    {
        // Replace with your actual data-fetching logic if needed
        $AttendanceDetails = getEmployeePeriodAttendnaceDetails($employeeId, $periodId, $date);
        $this->modalData = [
            'data' => $AttendanceDetails->toArray(),
            'date' => $date
        ];

        //  dd($this->modalData);
        $this->showDetailsModal = true; // This opens the modal
        $this->dispatch('open-modal', id: 'attendance-details');
    }
}
