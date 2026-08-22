<?php

namespace App\Filament\Clusters\HRSalaryCluster\Resources\EmployeeFinancialSummaryReportResource\Pages;

use App\Filament\Clusters\HRSalaryCluster\Resources\EmployeeFinancialSummaryReportResource;
use App\Models\Branch;
use App\Models\Employee;
use App\Modules\HR\PayrollReports\Services\EmployeeFinancialReportService;
use Filament\Resources\Pages\ListRecords;
use Mccarlosen\LaravelMpdf\Facades\LaravelMpdf as PDF;

class ListEmployeeFinancialSummaryReports extends ListRecords
{
    protected string $view = 'filament.pages.hr-reports.employee-financial-summary-report.pages.employee-financial-summary-report';
    protected static string $resource = EmployeeFinancialSummaryReportResource::class;

    protected function getViewData(): array
    {
        $branchId  = $this->getTable()->getFilters()['branch_id']->getState()['value'] ?? null;

        $items = collect([]);
        $employeeQuery = Employee::query();
        if ($branchId) {
            $employeeQuery->where('branch_id', $branchId);
        }

        $employeeIds = $employeeQuery->pluck('id')->toArray();
        if (!empty($employeeIds)) {
            $items = app(EmployeeFinancialReportService::class)->generateForEmployees($employeeIds);
        }

        return [
            'items'      => $items,
            'branch_id'  => $branchId,
            'summary'   => [
                'total_records' => $items->count(),
            ],
        ];
    }

    public function exportPdf()
    {
        $data = $this->getViewData();

        if ($data['items']->isEmpty()) {
            return;
        }

        $branch      = Branch::find($data['branch_id']);
        $branchName  = $branch?->name ?? __('lang.all_branches') ?? 'All Branches';
        
        $branchManager  = $branch?->user?->name ?? '-';
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

        $pdf = PDF::loadView('export.reports.employee-financial-summary-report-pdf', [
            'items'             => $data['items'],
            'summary'           => $data['summary'],
            'branchName'        => $branchName,
            'branch_id'         => $data['branch_id'],
            'companyLogo'       => $companyLogo,
            'branchManager'     => $branchManager,
            'financeManager'    => $financeManager,
        ], [], [
            'format'        => 'A4',
            'orientation'   => 'L', 
            'margin_left'   => 10,
            'margin_right'  => 10,
            'margin_top'    => 10,
            'margin_bottom' => 10,
        ]);

        $fileName = "Employee_Financial_Summary_Report_{$branchName}.pdf";

        return response()->streamDownload(function () use ($pdf, $fileName) {
            $pdf->stream($fileName);
        }, $fileName);
    }
}
