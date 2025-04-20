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

    public function mount(): void
    {
        $this->selectedCategory = request('category_id') ?? Category::first()?->id;
    }

    protected function getViewData(): array
    {
        $products = [];
        $reportData = [];

        if ($this->selectedCategory) {
            $products = Product::active()->get();

            $service = new MultiProductsInventoryService();
            foreach ($products as $product) {
                $inventory = $service->getInventoryForProduct($product->id);
                foreach ($inventory as $row) {
                    $reportData[] = [
                        'item_code' => $product->code,
                        'unit' => $row['unit_name'],
                        'category' => $product->category?->name,
                        'opening_stock' => 0, // replace with real logic if available
                        'total_orders' => 0, // replace with real logic if available
                        'remaining' => $row['remaining_qty'],
                        'calculated_stock' => 0 - 0 + $row['remaining_qty'],
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
