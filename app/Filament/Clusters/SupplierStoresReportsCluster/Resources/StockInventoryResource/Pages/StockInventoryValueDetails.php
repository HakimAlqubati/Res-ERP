<?php

namespace App\Filament\Clusters\SupplierStoresReportsCluster\Resources\StockInventoryResource\Pages;

use App\Filament\Clusters\SupplierStoresReportsCluster\Resources\StockInventoryResource;
use App\Models\StockInventory;
use App\Services\Financial\ClosingStockCalculationService;
use Filament\Resources\Pages\Page;
use Illuminate\Contracts\Support\Htmlable;

class StockInventoryValueDetails extends Page
{
    protected static string $resource = StockInventoryResource::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-table-cells';

    protected string $view = 'filament.pages.stock-inventory.stock-inventory-value-details';

    /** @var StockInventory */
    public StockInventory $inventory;

    /** @var array */
    public array $rows = [];

    /** @var float */
    public float $grandTotal = 0.0;

    public function mount(int $record): void
    {
        $this->inventory = StockInventory::with(['store', 'details.product', 'details.unit'])
            ->findOrFail($record);

        $service    = app(ClosingStockCalculationService::class);
        $this->rows = $service->getDetailedClosingStockValues($this->inventory);

        $this->grandTotal = array_sum(array_column($this->rows, 'total_value'));
    }

    public function getTitle(): string|Htmlable
    {
        return 'Stock Value Details — ' . ($this->inventory->store->name ?? 'N/A')
            . ' — ' . $this->inventory->inventory_date;
    }
}
