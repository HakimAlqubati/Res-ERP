<?php

namespace App\Filament\Clusters\InventoryReportCluster\Resources\InventoryTransactions\Pages;

use App\Filament\Clusters\InventoryReportCluster\Resources\InventoryTransactions\CompoundProductComponentsStockReportResource;
use App\Filament\Traits\HasBackButtonAction;
use App\Modules\Stock\Actions\Manufacturing\GetCompoundProductComponentsStockAction;
use Filament\Resources\Pages\ListRecords;

class ListCompoundProductComponentsStockReport extends ListRecords
{
    use HasBackButtonAction;

    protected static string $resource = CompoundProductComponentsStockReportResource::class;

    protected string $view = 'filament.pages.inventory-reports.compound-product-components-stock-report';

    protected function getHeaderActions(): array
    {
        return [];
    }

    protected function getViewData(): array
    {
        $storeId = $this->getTable()->getFilters()['store_id']->getState()['value'] ?? null;
        $compoundProductId = $this->getTable()->getFilters()['compound_product_id']->getState()['value'] ?? null;

        if (! $storeId || ! $compoundProductId) {
            return [
                'storeId'           => null,
                'compoundProductId' => null,
                'reportResult'      => null,
            ];
        }

        /** @var GetCompoundProductComponentsStockAction $action */
        $action = app(GetCompoundProductComponentsStockAction::class);
        $reportResult = $action->execute((int) $compoundProductId, (int) $storeId);

        // Fetch compound product details for the view header
        $compoundProduct = \App\Models\Product::find($compoundProductId);

        return [
            'storeId'           => $storeId,
            'compoundProductId' => $compoundProductId,
            'compoundProduct'   => $compoundProduct,
            'reportResult'      => $reportResult,
        ];
    }
}
