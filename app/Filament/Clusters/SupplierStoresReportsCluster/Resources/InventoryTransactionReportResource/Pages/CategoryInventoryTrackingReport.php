<?php

namespace App\Filament\Clusters\SupplierStoresReportsCluster\Resources\InventoryTransactionReportResource\Pages;

use App\Filament\Clusters\SupplierStoresReportsCluster\Resources\InventoryTransactionTruckingReportResource;
use App\Models\Category;
use App\Models\Product;
use App\Services\MultiProductsInventoryService;
use Filament\Resources\Pages\Page;

class CategoryInventoryTrackingReport extends Page
{
    protected static string $resource = InventoryTransactionTruckingReportResource::class;

    protected static string $view = 'filament.clusters.inventory-report-cluster.resources.inventory-transaction-trucking-report-resource.pages.category-inventory-tracking-report';

    public ?int $selectedCategory = null;

    public function mount(): void
    {
        $this->selectedCategory = request('category_id') ?? Category::first()?->id;
    }
    protected function getViewData(): array
    {
        $products = [];

        if ($this->selectedCategory) {
            $products = Product::with('unitPrices')
                ->where('category_id', $this->selectedCategory)
                ->get();
        }

        // $service = new MultiProductsInventoryService();
        // $productInventory = $service->getInventoryForProduct(1);
        // dd($productInventory);
        return [
            'categories' => Category::all(),
            'selectedCategory' => $this->selectedCategory,
            'products' => $products,
        ];
    }
}
