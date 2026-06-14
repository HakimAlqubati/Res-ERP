<?php
namespace App\Filament\Clusters\SupplierStoresReportsCluster\Resources\FifoBatchReportResource\Pages;

use App\Filament\Traits\HasBackButtonAction;
use App\Filament\Clusters\SupplierStoresReportsCluster\Resources\FifoBatchReportResource;
use App\Modules\Stock\Reports\FifoBatchReport\Contracts\FifoBatchServiceInterface;
use App\Modules\Stock\Reports\FifoBatchReport\DTOs\FifoBatchFilterDTO;
use Filament\Resources\Pages\ListRecords;
use App\Models\Product;

class ListFifoBatchReport extends ListRecords
{
    use HasBackButtonAction;
    protected static string $resource = FifoBatchReportResource::class;
    
    protected string $view = 'filament.pages.inventory-reports.fifo-batch-report';

    protected function getViewData(): array
    {
        $filters = $this->getTable()->getFilters();
        
        $productId   = $filters['product_id']->getState()['value'] ?? null;
        $storeId     = $filters['store_id']->getState()['value'] ?? null;
        $categoryId  = $filters['category_id']->getState()['value'] ?? null;
        $dateFrom    = $filters['date_range']->getState()['date_from'] ?? null;
        $dateTo      = $filters['date_range']->getState()['date_to'] ?? null;

        // If category is selected but no specific product, we should fetch products for that category
        $productIds = [];
        if ($productId) {
            $productIds = [$productId];
        } elseif ($categoryId) {
            $productIds = Product::where('category_id', $categoryId)->pluck('id')->toArray();
        }

        $filterDto = new FifoBatchFilterDTO(
            productIds: empty($productIds) ? null : $productIds,
            storeId: $storeId,
            dateFrom: $dateFrom,
            dateTo: $dateTo,
            excludeDepleted: true, // Only show batches that have remaining quantity
        );

        $reportService = app(FifoBatchServiceInterface::class);
        $reportData = $reportService->getReport($filterDto);

        return [
            'reportData' => $reportData,
            'storeId' => $storeId,
            'pagination' => null
        ];
    }
}
