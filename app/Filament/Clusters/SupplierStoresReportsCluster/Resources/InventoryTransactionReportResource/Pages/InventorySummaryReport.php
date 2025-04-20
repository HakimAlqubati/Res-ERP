<?php

namespace App\Filament\Clusters\SupplierStoresReportsCluster\Resources\InventoryTransactionReportResource\Pages;

use App\Filament\Clusters\SupplierStoresReportsCluster\Resources\InventoryTransactionTruckingReportResource;
use App\Models\Category;
use App\Models\Product;
use App\Services\MultiProductsInventoryService;
use Filament\Resources\Pages\Page;

class InventorySummaryReport extends Page
{
    protected static string $resource = InventoryTransactionTruckingReportResource::class;

    protected static string $view = 'filament.clusters.inventory-report-cluster.resources.inventory-transaction-trucking-report-resource.pages.inventory-summary-report';

    public ?int $selectedCategory = null;
    public  $showWithoutZero = 0;

    public function mount(): void
    {
        $this->selectedCategory = request('category_id') ?? Category::first()?->id;
        $this->showWithoutZero = request('show_without_zero') ?? false;
    }

    protected function getViewData(): array
    {
        $products = [];
        $reportData = [];

        if ($this->selectedCategory) {
            $products = Product::active()
                ->where('category_id', $this->selectedCategory)
                ->get();

            $service = new MultiProductsInventoryService();
            foreach ($products as $product) {
                $inventory = $service->getInventoryForProduct($product->id);
                $inventoryOpeningBalance = $service->getInventoryIn($product->id);

                $orderQuantities = $service->getInventoryOut($product->id);
                foreach ($inventory as $row) {
                    $filteredOpeningBalance = array_values(array_filter($inventoryOpeningBalance, function ($item) use ($product, $row) {
                        return $item['product_id'] == $product->id && $item['unit_id'] == $row['unit_id'];
                    }))[0] ?? null;
                    $filteredOrderQuantities = array_values(array_filter($orderQuantities, function ($item) use ($product, $row) {
                        return $item['product_id'] == $product->id && $item['unit_id'] == $row['unit_id'];
                    }))[0] ?? null;
                    $openingBalanceResult = $filteredOpeningBalance['quantity'] ?? 0;
                    $orderedQtyRes = $filteredOrderQuantities['quantity'] ?? 0;
                    $calculatedStock = $openingBalanceResult - ($orderedQtyRes + $row['remaining_qty']);
                    $calculatedStock = round($calculatedStock, 2);
                    if ($calculatedStock == 0 && $this->showWithoutZero == 1) {
                        continue;
                    }
                    $reportData[] = [
                        'product_code' => $product->code,
                        'product_id' => $product->id,
                        'product_name' => $product->name,
                        'unit_name' => $row['unit_name'],
                        'category' => $product->category?->name,
                        'opening_stock' => $openingBalanceResult,
                        'total_orders' => $orderedQtyRes,
                        'remaining_qty' => $row['remaining_qty'],
                        'calculated_stock' => $calculatedStock,
                    ];
                }
            }
        }
        return [
            'categories' => Category::all(),
            'selectedCategory' => $this->selectedCategory,
            'reportData' => $reportData,
            'products' => $products,
        ];
    }
}
