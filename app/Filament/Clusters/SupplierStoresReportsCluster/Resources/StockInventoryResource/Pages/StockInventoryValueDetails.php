<?php

namespace App\Filament\Clusters\SupplierStoresReportsCluster\Resources\StockInventoryResource\Pages;

use App\Filament\Clusters\SupplierStoresReportsCluster\Resources\StockInventoryResource;
use App\Models\StockInventory;
use App\Services\Financial\ClosingStockCalculationService;
use Carbon\Carbon;
use Filament\Resources\Pages\Page;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Facades\DB;

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

    /** Product IDs that have wrong package size in inventory transactions */
    public array $wrongProductIds = [];

    /** Whether to highlight wrong-package-size products */
    public bool $highlightWrong = false;

    public function mount(int $record): void
    {
        $this->inventory = StockInventory::with(['store', 'details.product', 'details.unit'])
            ->where('finalized',1)
            ->findOrFail($record)
            ;

        $service    = app(ClosingStockCalculationService::class);
        $this->rows = $service->getDetailedClosingStockValues($this->inventory);

        $this->grandTotal = array_sum(array_column($this->rows, 'total_value'));

        $this->wrongProductIds = $this->loadWrongPackageSizeProductIds();
    }

    /**
     * Detect products whose inventory transaction package_size differs from
     * the correct package_size in unit_prices, within the inventory's month and store.
     */
    private function loadWrongPackageSizeProductIds(): array
    {
        $inventoryDate = Carbon::parse($this->inventory->inventory_date);
        $startOfMonth  = $inventoryDate->copy()->startOfMonth()->toDateString();
        $endOfMonth    = $inventoryDate->copy()->endOfMonth()->toDateString();
        $storeId       = $this->inventory->store_id;

        return DB::table('inventory_transactions as it')
            ->join('unit_prices as up', function ($join) {
                $join->on('it.product_id', '=', 'up.product_id')
                     ->on('it.unit_id', '=', 'up.unit_id')
                     ->whereNull('up.deleted_at');
            })
            ->join('products as p', function ($join) {
                $join->on('p.id', '=', 'it.product_id')
                     ->whereNull('p.deleted_at');
            })
            ->whereNull('it.deleted_at')
            ->where('it.store_id', $storeId)
            ->where('it.movement_type', 'in')
            ->where('it.transactionable_type', 'App\Models\StockAdjustmentDetail')
            ->whereBetween(DB::raw('DATE(it.movement_date)'), [$startOfMonth, $endOfMonth])
            ->whereNotNull('it.package_size')
            ->whereNotNull('up.package_size')
            ->whereRaw('it.package_size != up.package_size')
            ->distinct()
            ->pluck('it.product_id')
            ->map(fn($id) => (int) $id)
            ->toArray();
    }

    /** Livewire toggle — called from the blade button */
    public function toggleHighlight(): void
    {
        $this->highlightWrong = ! $this->highlightWrong;
    }

    public function getTitle(): string|Htmlable
    {
        return 'Stock Value Details — ' . ($this->inventory->store->name ?? 'N/A')
            . ' — ' . $this->inventory->inventory_date;
    }
}
