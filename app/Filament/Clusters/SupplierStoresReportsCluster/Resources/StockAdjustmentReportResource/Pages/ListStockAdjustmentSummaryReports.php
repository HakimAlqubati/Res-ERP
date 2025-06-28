<?php

namespace App\Filament\Clusters\SupplierStoresReportsCluster\Resources\StockAdjustmentReportResource\Pages;

use App\Filament\Clusters\SupplierStoresReportsCluster\Resources\StockAdjustmentSummaryReportResource;
use App\Services\Inventory\StockAdjustmentByCategoryReportService;
use Filament\Resources\Pages\ListRecords;

class ListStockAdjustmentSummaryReports extends ListRecords
{
    use \App\Filament\Traits\HasBackButtonAction;

    protected static string $resource = StockAdjustmentSummaryReportResource::class;
    protected static string $view = 'filament.pages.inventory-reports.adjustment-summary-report';

    protected function getViewData(): array
    {
        $categoryIds = $this->getTable()->getFilters()['product.category_id']->getState()['values'] ?? null;

        $adjustmentType = $this->getTable()->getFilters()['adjustment_type']->getState()['value'] ?? null;

        $storeId = $this->getTable()->getFilters()['store_id']->getState()['value'] ?? null;
        $fromDate = $this->getTable()->getFilters()['from_date']->getState()['from_date'] ?? now()->startOfMonth();
        $toDate = $this->getTable()->getFilters()['to_date']->getState()['to_date'] ?? now()->endOfMonth();
 
        $reportData = app(StockAdjustmentByCategoryReportService::class)->generate(
            $adjustmentType,
            $fromDate,
            $toDate,
            $storeId,
            $categoryIds
        );

        return [
            'reportData' => $reportData,
            'adjustmentType' => $adjustmentType,
            'fromDate' => $fromDate,
            'toDate' => $toDate,
            'storeId' => $storeId,
        ];
    }

    public function getTableRecordKey($record): string
    {
        return $record['category'] . '-' . $record['adjustment_type'] . '-' . $record['store'];
    }
}