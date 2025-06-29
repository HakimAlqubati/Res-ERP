<?php

namespace App\Filament\Clusters\SupplierStoresReportsCluster\Resources\StockAdjustmentReportResource\Pages;

use App\Filament\Clusters\SupplierStoresReportsCluster\Resources\StockAdjustmentSummaryReportResource;
use App\Services\Inventory\StockAdjustmentByCategoryReportService;
use Filament\Resources\Pages\Page;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Str;

class ViewStockAdjustmentSummaryReport extends Page
{
    protected static string $resource = StockAdjustmentSummaryReportResource::class;
    protected static string $view = 'filament.pages.inventory-reports.stock-adjustment-summary-details';

    public string $category;
    public string $adjustment_type;
    public string $store;
    public string $totalPrice;

    public array $adjustments = [];

    public function mount(
        $categoryId,
        $adjustment_type,
        $storeId,
        $fromDate = null,
        $toDate = null
    ) {

        $categoryId = (int)$categoryId;

        $report = app(StockAdjustmentByCategoryReportService::class)->generate(
            adjustmentType: $adjustment_type,
            fromDate: $fromDate,
            toDate: $toDate,
            storeId: $storeId,
            categoryIds: [$categoryId],
            withDetails: true
        );
        $record = $report->first();

        $this->category = $record['category'] ?? 'Unknown Category';
        $this->store = $record['store'] ?? 'Unknown Store';
        $this->totalPrice = $record['total_price'] ?? '-';
        $this->adjustments = $record['adjustments']?->toArray() ?? [];
    }

    public function getTitle(): string
    {
        return "Details - {$this->category} ({$this->adjustment_type}) - {$this->store}";
    }
}