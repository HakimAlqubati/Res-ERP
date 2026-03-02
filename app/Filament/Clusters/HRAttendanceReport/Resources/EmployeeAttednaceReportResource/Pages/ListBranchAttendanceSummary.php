<?php

namespace App\Filament\Clusters\HRAttendanceReport\Resources\EmployeeAttednaceReportResource\Pages;

use App\Filament\Clusters\HRAttendanceReport\Resources\BranchAttendanceSummaryResource;
use App\Services\HR\BranchAttendanceSummaryService;
use Filament\Resources\Pages\ListRecords;

class ListBranchAttendanceSummary extends ListRecords
{
    protected static string $resource = BranchAttendanceSummaryResource::class;

    protected string $view = 'filament.pages.hr-reports.attendance.pages.branch-attendance-summary';

    protected function getViewData(): array
    {
        $branchId = $this->getTable()->getFilters()['branch_id']->getState()['value']
            ?? current($this->getTable()->getFilter('branch_id')->getState() ?: [])
            ?? null;

        $yearState  = $this->getTable()->getFilters()['year']->getState()['value'] ?? null;
        $monthState = $this->getTable()->getFilters()['month']->getState()['value'] ?? null;

        $year  = $yearState ? (int) $yearState : now()->year;
        $month = $monthState ? (int) $monthState : now()->month;

        $report = null;

        if ($branchId) {
            /** @var BranchAttendanceSummaryService $service */
            $service = app(BranchAttendanceSummaryService::class);
            $report  = $service->generate((int) $branchId, $year, $month);
        }

        return [
            'report'    => $report,
            'branch_id' => $branchId,
            'year'      => $year,
            'month'     => $month,
        ];
    }
}
