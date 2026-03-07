<?php

namespace App\Filament\Clusters\HRAttendanceReport\Resources\MissingCheckoutReportResource\Pages;

use App\Filament\Clusters\HRAttendanceReport\Resources\MissingCheckoutReportResource;
use App\Services\HR\AttendanceHelpers\Reports\MissingCheckoutService;
use Carbon\Carbon;
use Filament\Resources\Pages\ListRecords;

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
            'summary'   => [
                'total_records' => $items->count(),
            ],
        ];
    }
}
