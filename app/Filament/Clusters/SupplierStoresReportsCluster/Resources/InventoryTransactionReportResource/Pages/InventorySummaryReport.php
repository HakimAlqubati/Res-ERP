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
    public int|string $perPage = 15;
    public ?int $selectedProduct = null;


    public function mount(): void
    {
        $this->selectedCategory = request('category_id');
        $this->showWithoutZero = request('show_without_zero') ?? false;
        $perPageRequest = request('per_page');
        $this->selectedProduct = is_numeric(request('product_id')) ? (int) request('product_id') : null;



        $this->perPage = $perPageRequest === 'all' ? 'all' : (int) ($perPageRequest ?? 15);
    }

    protected function getViewData(): array
    {
        $products = [];
        $reportData = [];

        $query = Product::active();
        if (!empty($this->selectedCategory)) {
            $query->where('category_id', $this->selectedCategory);
        }
        if (!empty($this->selectedProduct)) {
            $query->where('id', $this->selectedProduct);
        }
        $products = empty($this->selectedCategory)
            ? (
                $this->perPage === 'all'
                ? $query->get()
                : $query->paginate($this->perPage)
            )
            : $query->get();
        $storeId = 0;
        $service = new MultiProductsInventoryService(storeId: $storeId);
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
                $calculatedStock = $row['remaining_qty'] >= 0 ? $openingBalanceResult - ($orderedQtyRes + $row['remaining_qty']) : $openingBalanceResult - ($orderedQtyRes);
                $calculatedStock = round($calculatedStock, 2);
                if (($calculatedStock >= 0.01 && $calculatedStock <= 0.03) ||
                    (abs($calculatedStock) < 0.00001)
                    ||
                    ($calculatedStock <= -0.01 && $calculatedStock >= -0.03)
                ) {
                    $calculatedStock = 0;
                }
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
        return [
            'categories' => Category::all(),
            'selectedCategory' => $this->selectedCategory,
            'reportData' => $reportData,
            'products' => $products,
        ];
    }
}
