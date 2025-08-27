<?php

namespace App\Filament\Clusters\SupplierStoresReportsCluster\Resources\InventoryTransactionReportResource\Pages;

use App\Filament\Traits\HasBackButtonAction;
use App\Filament\Clusters\SupplierStoresReportsCluster\Resources\InboundOutflowReportResource;
use App\Services\Inventory\InboundOutflowReportService;
use Filament\Resources\Pages\ListRecords;

class ListInboundOutflowReport extends ListRecords
{
    use HasBackButtonAction;
    protected static string $resource = InboundOutflowReportResource::class;
    protected string $view = 'filament.pages.inventory-reports.inbound-outflow-report';

    protected function getViewData(): array
    {
        $filters = $this->getTable()->getFilters();

        $transactionableId = $filters['transactionable_id']->getState()['transactionable_id'] ?? null;
        $transactionableType = $filters['transactionable_id']->getState()['transactionable_type'] ?? null;

        if (!$transactionableId) {
            return ['reportData' => []];
        }

        $service = new InboundOutflowReportService();
        $reportData = $service->generate((int) $transactionableId, $transactionableType);

        return ['reportData' => $reportData];
    }
}
