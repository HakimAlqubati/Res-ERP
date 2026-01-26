<?php

namespace App\Filament\Clusters\SupplierStoresReportsCluster\Resources\InventorySummaryReportResource\Pages;

use App\Filament\Traits\HasBackButtonAction;
use App\Filament\Clusters\SupplierStoresReportsCluster\Resources\InventorySummaryReportResource;
use App\Services\Inventory\Summary\InventorySummaryReportService;
use Filament\Resources\Pages\ListRecords;

class ListInventorySummaryReport extends ListRecords
{
    use HasBackButtonAction;

    protected static string $resource = InventorySummaryReportResource::class;
    protected string $view = 'filament.pages.inventory-reports.inventory-summary-report';

    public $perPage = 15;

    protected function getViewData(): array
    {
        // جلب قيم الفلاتر
        $productIds = $this->getTable()->getFilters()['product_id']->getState()['values'] ?? [];
        if (!is_array($productIds)) {
            $productIds = [$productIds];
        }
        $productIds = array_filter($productIds);

        $storeId = $this->getTable()->getFilters()['store_id']->getState()['value'] ?? null;
        $categoryId = $this->getTable()->getFilters()['category_id']->getState()['value'] ?? null;
        $onlyAvailable = $this->getTable()->getFilters()['show_extra_fields']->getState()['only_available'] ?? false;

        // بناء الـ Service
        $service = InventorySummaryReportService::make()->withDetails();

        if ($storeId) {
            $service->store((int) $storeId);
        }

        if (!empty($productIds)) {
            $service->products(array_map('intval', $productIds));
        }

        if ($categoryId) {
            $service->category((int) $categoryId);
        }

        if ($onlyAvailable) {
            $service->onlyAvailable();
        }

        // جلب البيانات مع Pagination
        $perPage = $this->perPage === 'all' ? 9999 : (int) $this->perPage;
        $pagination = $service->paginate($perPage);

        // تحويل البيانات للشكل المطلوب
        $reportData = $pagination->getCollection()->map(function ($item) {
            return [
                'product_id' => $item->product_id,
                'product_code' => $item->product?->code ?? 'N/A',
                'product_name' => $item->product?->name ?? 'N/A',
                'unit_id' => $item->unit_id,
                'unit_name' => $item->unit?->name ?? 'N/A',
                'package_size' => $item->package_size,
                'remaining_qty' => $item->remaining_qty,
            ];
        })->groupBy('product_id')->toArray();

        return [
            'reportData' => $reportData,
            'storeId' => $storeId,
            'pagination' => $pagination,
        ];
    }
}
