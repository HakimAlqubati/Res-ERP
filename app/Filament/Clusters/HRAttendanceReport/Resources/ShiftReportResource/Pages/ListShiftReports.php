<?php

namespace App\Filament\Clusters\HRAttendanceReport\Resources\ShiftReportResource\Pages;

use App\Filament\Clusters\HRAttendanceReport\Resources\ShiftReportResource;
use App\Services\HR\AttendanceHelpers\Reports\ShiftReportService;
use Filament\Resources\Pages\ListRecords;
use App\Models\Branch;
use Mccarlosen\LaravelMpdf\Facades\LaravelMpdf as PDF;

class ListShiftReports extends ListRecords
{
    protected string $view = 'filament.pages.hr-reports.shift-report.pages.shift-report';
    protected static string $resource = ShiftReportResource::class;

    protected function getViewData(): array
    {
        $branchId  = $this->getTable()->getFilters()['branch_id']->getState()['value'] ?? null;
        $periodState = $this->getTable()->getFilters()['period_id']->getState();
        $periodIds = $periodState['values'] ?? $periodState['value'] ?? [];

        $filters = [];
        if ($branchId && !empty($periodIds)) {
            $filters['branch_id'] = $branchId;
            $filters['period_id'] = (array) $periodIds;
            $items = app(ShiftReportService::class)->getEmployeesInShift($filters);
        } else {
            $items = collect([]);
        }

        return [
            'items'      => $items,
            'branch_id'  => $branchId,
            'period_ids' => $periodIds,
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
        $branchName  = $branch?->name ?? 'Branch';
        
        $periods     = \App\Models\WorkPeriod::whereIn('id', $data['period_ids'])->pluck('name')->toArray();
        $periodName  = !empty($periods) ? implode(', ', $periods) : 'Shifts';

        $branchManager  = $branch?->user?->name ?? '';
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

        $pdf = PDF::loadView('export.reports.shift-report-pdf', [
            'items'             => $data['items'],
            'branchName'        => $branchName,
            'periodName'        => $periodName,
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

        $fileName = "Shift_Report_{$branchName}_{$periodName}.pdf";

        return response()->streamDownload(function () use ($pdf, $fileName) {
            $pdf->stream($fileName);
        }, $fileName);
    }
}
