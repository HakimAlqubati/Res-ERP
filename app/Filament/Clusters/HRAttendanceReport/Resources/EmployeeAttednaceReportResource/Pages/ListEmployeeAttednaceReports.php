<?php

namespace App\Filament\Clusters\HRAttendanceReport\Resources\EmployeeAttednaceReportResource\Pages;

use Filament\Schemas\Components\Tabs\Tab;
use App\Filament\Clusters\HRAttendanceReport\Resources\EmployeeAttednaceReportResource;
use App\Models\Employee;
use App\Services\HR\AttendanceHelpers\EmployeePeriodHistoryService;
use App\Services\HR\AttendanceHelpers\Reports\AttendanceFetcher;
use App\Services\HR\AttendanceHelpers\Reports\HelperFunctions;
use Carbon\Carbon;
use Filament\Resources\Pages\ListRecords;

class ListEmployeeAttednaceReports extends ListRecords
{
    protected   string $view     = 'filament.pages.hr-reports.attendance.pages.attendance-employee-new';
    protected static string $resource = EmployeeAttednaceReportResource::class;

    public $showDetailsModal = false;
    public $modalData        = [];
    public function refreshData()
    {
        
        // حالياً فارغة
        // لاحقاً ضع هنا منطق إعادة جلب البيانات
    }
    public function showDetails($date, $employeeId, $periodId)
    {

        $attendanceFetcher      = new AttendanceFetcher(new EmployeePeriodHistoryService());
        $attendanceDetails      = $attendanceFetcher->getEmployeePeriodAttendnaceDetails($employeeId, $periodId, $date);
        $this->modalData        = $attendanceDetails->toArray();
        $this->showDetailsModal = true;
    }

    protected function getViewData(): array
    {
        if (! isStuff()) {
            $employee_id = $this->getTable()->getFilters()['employee_id']->getState()['value'];
        } else {
            $employee_id = auth()->user()?->employee?->id;
        }

        $employee  = Employee::find($employee_id);
        $startDate = $this->getTable()->getFilters()['date_range']->getState()['start_date'];
        $endDate   = $this->getTable()->getFilters()['date_range']->getState()['end_date'];
        $showDay   = $this->getTable()->getFilters()['show_extra_fields']->getState()['show_day'];
        // $historyService = new EmployeePeriodHistoryService();
        $startDate = Carbon::parse($startDate);
        $endDate   = Carbon::parse($endDate);
        // $data     = $historyService->getEmployeePeriodsByDateRange($employee, $startDate, $endDate);
        $attendanceFetcher = new AttendanceFetcher(new EmployeePeriodHistoryService());
        $data              = $employee ? $attendanceFetcher->fetchEmployeeAttendances($employee, $startDate, $endDate) : [];
        $chartData         = HelperFunctions::getAttendanceChartData($data, $employee);

        // Initialize total counters
        $totalSupposed = '0 h 0 m';
        $totalWorked   = 0;
        $totalApproved = 0;
        $totalMinutes  = 0;

        return [
            'report_data'   => $data,
            'show_day'      => $showDay,
            'employee_id'   => $employee_id,
            'start_date'    => $startDate?->format('Y-m-d') ?? '',
            'end_date'      => $endDate?->format('Y-m-d') ?? '',
            'totalSupposed' => $totalSupposed,
            'totalWorked'   => $this->formatDuration($totalWorked),
            'totalApproved' => $this->formatDuration($totalApproved),
            'chartData'     => $chartData['chartData'],
            'employee_name' => $chartData['employee_name'],
            'total_actual_duration_hours' => $data['total_actual_duration_hours'] ?? 0,
            'total_duration_hours' => $data['total_duration_hours'] ?? 0,
            'total_approved_overtime' => $data['total_approved_overtime'] ?? 0,
        ];
    }

    private function parseDuration($duration)
    {
        if (preg_match('/(\d+)\s*h\s*(\d+)\s*m/', $duration, $matches)) {
            $hours   = (int) $matches[1];
            $minutes = (int) $matches[2];
            return $hours * 60 + $minutes; // Convert to total minutes
        }
        return 0;
    }

    private function formatDuration($totalMinutes)
    {
        $hours   = intdiv($totalMinutes, 60);
        $minutes = $totalMinutes % 60;
        return "{$hours} h {$minutes} m";
    }
}
