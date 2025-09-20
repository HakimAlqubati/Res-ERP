<?php

namespace App\Filament\Resources\InVSReportResource\Pages;

use App\Filament\Resources\InVSOutResellerReportResource;
use App\Filament\Traits\HasBackButtonAction;
use App\Models\Store;
use App\Services\Reports\CenteralKitchens\InVsOutReportService;
use App\Services\Reports\CenteralKitchens\InVsOutResellerReportService;
use App\Services\StockSupply\Reports\StockSupplyOrderReportService;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Model;

class ListInVSOutResellerReport extends ListRecords
{
    protected static string $resource = InVSOutResellerReportResource::class;
    protected string $view = 'filament.pages.stock-report.in-vs-out-reseller-report';



    protected function getViewData(): array
    {
        $storeState = $this->getTable()->getFilters()['store_id']->getState();
        $storeId = is_array($storeState) ? ($storeState['value'] ?? null) : $storeState;

        $dateState = $this->getTable()->getFilters()['date_range']->getState();
        $fromDate = $dateState['from_date'] ?? null;
        $toDate   = $dateState['to_date'] ?? null;

        // ابدأ من غير store_id
        $filters = [
            'from_date' => $fromDate,
            'to_date'   => $toDate,
            'stores'    => Store::active()
                ->whereHas('branches', fn($q) => $q->resellers())
                ->pluck('name', 'id')
                ->toArray(),
        ];

        // أضِف store_id فقط عندما تكون قيمة حقيقية
        if (filled($storeId)) {
            $filters['store_id'] = (int) $storeId;
        }

        $reportService = new InVsOutResellerReportService();
        $data = $reportService->getFinalComparison($filters);

        $store = filled($storeId) ? (Store::find($storeId)?->name) : null;

        return [
            'reportData' => $data,
            'store'      => $store,
            'toDate'     => $toDate,
        ];
    }
}
