<?php

namespace App\Filament\Clusters\HRAttendanceReport\Resources\MissingCheckoutReportResource\Pages;

use App\Filament\Clusters\HRAttendanceReport\Resources\MissingCheckoutReportResource;
use App\Services\HR\AttendanceHelpers\Reports\MissingCheckoutService;
use Carbon\Carbon;
use Filament\Resources\Pages\ListRecords;
use App\Models\Branch;
use Mccarlosen\LaravelMpdf\Facades\LaravelMpdf as PDF;

class ListMissingCheckoutReports extends ListRecords
{
    protected string $view = 'filament.pages.hr-reports.missing-checkout.pages.missing-checkout-report';
    protected static string $resource = MissingCheckoutReportResource::class;

    protected function getViewData(): array
    {
        $branchId = $this->getTable()->getFilters()['branch_id']->getState()['value'] ?? null;

        $dateFrom = $this->getTable()->getFilters()['date_range']->getState()['date_from'] ?? Carbon::today()->startOfMonth()->toDateString();
        $dateTo   = $this->getTable()->getFilters()['date_range']->getState()['date_to'] ?? Carbon::today()->endOfMonth()->toDateString();

        $filters = [];
        if ($branchId) {
            $filters['branch_id'] = $branchId;
            $items = app(MissingCheckoutService::class)->getMissingCheckouts($dateFrom, $dateTo, $filters);
        } else {
            $items = collect([]);
        }

        return [
            'items'     => $items,
            'branch_id' => $branchId,
            'date_from' => $dateFrom,
            'date_to'   => $dateTo,
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

        $pdf = PDF::loadView('export.reports.missing-checkout-report-pdf', [
            'items'             => $data['items'],
            'branchName'        => $branchName,
            'dateFrom'          => $data['date_from'],
            'dateTo'            => $data['date_to'],
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

        $fileName = "Missing_Checkout_{$branchName}_{$data['date_from']}_to_{$data['date_to']}.pdf";

        return response()->streamDownload(function () use ($pdf, $fileName) {
            $pdf->stream($fileName);
        }, $fileName);
    }
}
