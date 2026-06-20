<?php

namespace App\Filament\Clusters\InventoryReportCluster\Resources\InventoryTransactions\Pages;

use App\Filament\Clusters\InventoryReportCluster\Resources\InventoryTransactions\StockPositionBatchReportResource;
use App\Filament\Traits\HasBackButtonAction;
use App\Modules\Stock\Reports\FifoBatchReports\Contracts\GetAvailableStockBatchesQueryInterface;
use App\Modules\Stock\Reports\FifoBatchReports\DataTransferObjects\StockBatchFilterDTO;
use Filament\Resources\Pages\ListRecords;

class ListStockPositionBatchReport extends ListRecords
{
    use HasBackButtonAction;

    protected static string $resource = StockPositionBatchReportResource::class;

    protected string $view = 'filament.pages.inventory-reports.stock-position-batch-report';

    protected function getHeaderActions(): array
    {
        return [];
    }

    protected function getViewData(): array
    {
        $storeId = $this->getTable()->getFilters()['store_id']->getState()['value'] ?? null;
        $categoryId = $this->getTable()->getFilters()['category_id']->getState()['value'] ?? null;
        $productIds = $this->getTable()->getFilters()['product_ids']->getState()['values'] ?? [];
        $isCurrentBatch = $this->getTable()->getFilters()['current_batch']->getState()['value'] ?? null;

        if (! $storeId) {
            return [
                'storeId'      => null,
                'reportResult' => null,
            ];
        }

        // تحويل الفلاتر إلى DTO
        $filters = new StockBatchFilterDTO(
            storeId: (int) $storeId,
            productIds: array_map('intval', array_filter($productIds)),
            isCurrentBatch: $isCurrentBatch !== null && $isCurrentBatch !== ''
                ? (bool) $isCurrentBatch
                : null,
            categoryId: $categoryId ? (int) $categoryId : null,
            perPage: 50,
            page: $this->paginators['reportPage'] ?? 1,
        );

        /** @var GetAvailableStockBatchesQueryInterface $query */
        $query = app(GetAvailableStockBatchesQueryInterface::class);
        $reportResult = $query->execute($filters);

        return [
            'storeId'      => $storeId,
            'reportResult' => $reportResult,
        ];
    }
}
