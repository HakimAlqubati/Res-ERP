<?php
namespace App\Filament\Resources\StockCostReportResource\Pages;

use App\Filament\Resources\StockCostReportResource;
use App\Models\Store;
use App\Services\InventoryReports\StoreCostReportService;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListStockCostReports extends ListRecords
{
    protected static string $resource = StockCostReportResource::class;
    protected string $view     = 'filament.pages.stock-report.stock-cost-report';
    public $perPage = 15;
    protected function getHeaderActions(): array
    {
        return [
            // Actions\CreateAction::make(),
        ];
    }

    protected function getViewData(): array
    {
        $storeId        = $this->getTable()->getFilters()['store_id']->getState()['value'] ?? null;
        $fromDate       = $this->getTable()->getFilters()['date']->getState()['from_date'] ?? null;
        $toDate         = $this->getTable()->getFilters()['date']->getState()['to_date'] ?? null;
        $returnableTypes = $this->getTable()->getFilters()['returnable_type']->getState()['values'] ?? [];

        // $returnableTypes = $returnableType ? [$returnableType] : [
        //     \App\Models\ReturnedOrder::class,
        //     \App\Models\Order::class,
        //     \App\Models\StockAdjustmentDetail::class,
        //     \App\Models\StockIssueOrder::class,
        // ];
 
        
        $perPage = $this->perPage;

        if ($perPage === 'all') {
            $perPage = 9999; // أو أي عدد كبير جدًا لضمان عرض الكل
        }

      

        $reportService = new StoreCostReportService(
            storeId: $storeId,
            fromDate: $fromDate??'',
            toDate: $toDate??'',
            perPage: $perPage,
            returnableTypes:  $returnableTypes 
            // returnableTypes: [
            //     \App\Models\ReturnedOrder::class,
            //     \App\Models\Order::class,
            //     \App\Models\StockAdjustmentDetail::class,
            //     \App\Models\StockIssueOrder::class,
            // ]
        );

        $data  = $reportService->generate();
        $store = Store::find($storeId)?->name;

        return [
            'reportData' => $data,
            'store'      => $store,
            'fromDate'   => $fromDate,
            'toDate'     => $toDate,
        ];
    }
}