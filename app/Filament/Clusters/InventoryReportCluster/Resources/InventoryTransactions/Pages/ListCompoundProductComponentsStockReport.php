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
        $categoryId = $this->getTable()->getFilters()['category_id']->getState()['value'] ?? null;

        if (! $storeId || (! $compoundProductId && ! $categoryId)) {
            return [
                'storeId'           => null,
                'compoundProductId' => null,
                'categoryId'        => null,
                'reportResult'      => null,
                'compoundProduct'   => null,
                'category'          => null,
            ];
        }

        /** @var GetCompoundProductComponentsStockAction $action */
        $action = app(GetCompoundProductComponentsStockAction::class);
        $reportResult = $action->execute((int) $compoundProductId, (int) $storeId, (int) $categoryId);

        // Fetch compound product details for the view header
        $compoundProduct = $compoundProductId ? \App\Models\Product::find($compoundProductId) : null;
        $category = $categoryId ? \App\Models\Category::find($categoryId) : null;

        return [
            'storeId'           => $storeId,
            'compoundProductId' => $compoundProductId,
            'categoryId'        => $categoryId,
            'compoundProduct'   => $compoundProduct,
            'category'          => $category,
            'reportResult'      => $reportResult,
        ];
    }
}
