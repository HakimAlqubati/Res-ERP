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
        $productState = $this->getTable()->getFilters()['product_id']->getState();
        $productId = is_array($productState) ? ($productState['value'] ?? null) : $productState;

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
        if (filled($productId)) {
            $filters['product_id'] = (int) $productId;
        }
        $reportService = new InVsOutResellerReportService();
        $data = $reportService->getFinalComparison($filters);

        $store = filled($storeId) ? (Store::find($storeId)?->name) : null;

        $totals = null;
        if (filled($productId)) {
            $totals = [
                'in_qty'      => collect($data)->sum('in_qty'),
                'out_qty'     => collect($data)->sum('out_qty'),
                'current_qty' => collect($data)->sum('current_qty'),
            ];
        }

        return [
            'reportData' => $data,
            'store'      => $store,
            'toDate'     => $toDate,
            'product_id' => $filters['product_id'],
            'totals'     => $totals,
        ];
    }
}
