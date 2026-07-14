<?php

declare(strict_types=1);

namespace App\Modules\Stock\Actions\Manufacturing;

use App\Models\ProductItem;
use App\Modules\Stock\Reports\StockBalanceReport\Contracts\StockBalanceRepositoryInterface;
use App\Modules\Stock\Reports\StockBalanceReport\DataTransferObjects\StockBalanceFilterDTO;
use Illuminate\Support\Collection;

final class GetCompoundProductComponentsStockAction
{
    public function __construct(
        private readonly StockBalanceRepositoryInterface $stockBalanceRepo
    ) {}

    /**
     * @return Collection<int, array>
     */
    public function execute(int $compoundProductId, int $storeId, int $categoryId = 0): Collection
    {
        // 1. Fetch components of the compound product(s)
        $componentsQuery = ProductItem::with(['product', 'unit']);

        if ($compoundProductId) {
            $componentsQuery->where('parent_product_id', $compoundProductId);
        } elseif ($categoryId) {
            $compoundProductIds = \App\Models\Product::where('category_id', $categoryId)
                ->manufacturingCategory()
                ->pluck('id');
            $componentsQuery->whereIn('parent_product_id', $compoundProductIds);
        } else {
            return collect();
        }

        $components = $componentsQuery->get();

        if ($components->isEmpty()) {
            return collect();
        }

        // Fetch parent products to show grouping headers in the view
        $parentProductIds = $components->pluck('parent_product_id')->unique()->toArray();
        $parentProducts = \App\Models\Product::whereIn('id', $parentProductIds)->get()->keyBy('id');

        // 2. Fetch balances for these components in the specific store
        $productIds = $components->pluck('product_id')->unique()->toArray();
        
        $filters = new StockBalanceFilterDTO(
            storeId: $storeId,
            productIds: $productIds
        );

        // Fetch balances and index by ID for O(1) lookup performance
        $balances = $this->stockBalanceRepo->getBalances($filters)->keyBy('id');

        // 3. Map the result to include component details and available balance
        return $components->map(function (ProductItem $component) use ($balances, $parentProducts) {
            $balanceModel = $balances->get($component->product_id);
            $availableQty = 0.0;

            if ($balanceModel) {
                // Best Practice & Performance: Calculate purely mathematically
                $availableQty = (float) ($balanceModel->total_in ?? 0) - (float) ($balanceModel->total_out ?? 0);
            }

            $requiredQuantityForOneUnit = $this->calculateRequiredQuantity(
                (float) $component->quantity,
                (float) ($component->qty_waste_percentage ?? 0)
            );

            $parentProduct = $parentProducts->get($component->parent_product_id);

            return [
                'compound_product_id' => $component->parent_product_id,
                'compound_product_code' => $parentProduct ? $parentProduct->code : '-',
                'compound_product_name' => $parentProduct ? $parentProduct->name : "Product ID #{$component->parent_product_id}",
                'product_id' => $component->product_id,
                'product_code' => $component->product ? $component->product->code : '-',
                'product_name' => $component->product ? $component->product->name : "Product ID #{$component->product_id}",
                'unit_id' => $component->unit_id,
                'unit_name' => $component->unit ? $component->unit->name : null,
                'package_size' => (float) $component->package_size,
                'recipe_quantity' => (float) $component->quantity,
                'waste_percentage' => (float) ($component->qty_waste_percentage ?? 0),
                'required_quantity_for_one_unit' => $requiredQuantityForOneUnit,
                'available_balance' => round($availableQty, 4),
                'has_shortage' => round($availableQty, 4) < round($requiredQuantityForOneUnit, 4),
            ];
        });
    }

    private function calculateRequiredQuantity(float $recipeQty, float $wastePercentage): float
    {
        return $recipeQty * (1 + ($wastePercentage / 100));
    }
}
