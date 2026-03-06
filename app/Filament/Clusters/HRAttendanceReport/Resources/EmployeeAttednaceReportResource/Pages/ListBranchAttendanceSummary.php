<?php

namespace App\Filament\Clusters\HRAttendanceReport\Resources\EmployeeAttednaceReportResource\Pages;

use App\Filament\Clusters\HRAttendanceReport\Resources\BranchAttendanceSummaryResource;
use App\Models\Branch;
use App\Services\HR\BranchAttendanceSummaryService;
use Filament\Resources\Pages\ListRecords;
use Mccarlosen\LaravelMpdf\Facades\LaravelMpdf as PDF;

class ListBranchAttendanceSummary extends ListRecords
{
    protected static string $resource = BranchAttendanceSummaryResource::class;

    protected string $view = 'filament.pages.hr-reports.attendance.pages.branch-attendance-summary';

    protected function getViewData(): array
    {
        $branchId = $this->getTable()->getFilters()['branch_id']->getState()['value']
            ?? current($this->getTable()->getFilter('branch_id')->getState() ?: [])
            ?? null;

        $monthState = $this->getTable()->getFilters()['month']->getState()['value'] ?? null;

        // The month filter value is in "Month Year" format, e.g. "March 2026"
        if ($monthState) {
            $parsed = \Carbon\Carbon::createFromFormat('F Y', $monthState);
            $year  = (int) $parsed->year;
            $month = (int) $parsed->month;
        }

        $report = null;

        if ($branchId && isset($year) && isset($month)) {
            /** @var BranchAttendanceSummaryService $service */
            $service = app(BranchAttendanceSummaryService::class);
            $report  = $service->generate((int) $branchId, $year, $month);
        }
        return [
            'report'    => $report,
            'branch_id' => $branchId,
            'year'      => $year ?? null,
            'month'     => $month ?? null,
        ];
    }

    public function exportPdf()
    {
        $data = $this->getViewData();

        if (!$data['report']) {
            return;
        }

        $branch = Branch::find($data['branch_id']);
        $branchName = $branch?->name ?? 'Branch';

        $branchManager = $branch?->user?->name ?? '';
        $financeManager = \App\Models\User::whereHas('roles', function ($query) {
            $query->where('id', 16);
        })->first()?->name ?? '';
        // Company logo
        $companyLogo = \App\Models\Setting::getSetting('company_logo');
        if ($companyLogo) {
            $companyLogo = public_path('storage/' . $companyLogo);
            if (!file_exists($companyLogo)) {
                $companyLogo = null;
            }
        }

        $pdf = PDF::loadView('export.reports.branch-attendance-summary-pdf', [
            'report'            => $data['report'],
            'branchName'        => $branchName,
            'year'              => $data['year'],
            'month'             => $data['month'],
            'companyLogo'       => $companyLogo,
            'branchManager'     => $branchManager,
            'operationManager'  => null,
            'sustainingManager' => null,
            'financeManager'    => $financeManager,
        ], [], [
            'format'        => 'A4',
            'orientation'   => 'P',
            'margin_left'   => 10,
            'margin_right'  => 10,
            'margin_top'    => 10,
            'margin_bottom' => 10,
        ]);

        $monthName = \Carbon\Carbon::create($data['year'], $data['month'])->format('M');
        $fileName = "Attendance_Report_{$branchName}_{$monthName}_{$data['year']}.pdf";

        return response()->streamDownload(function () use ($pdf, $fileName) {
            $pdf->stream($fileName);
        }, $fileName);
    }
}
