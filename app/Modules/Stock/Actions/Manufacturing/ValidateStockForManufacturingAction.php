<?php

declare(strict_types=1);

namespace App\Modules\Stock\Actions\Manufacturing;

use App\Models\StockSupplyOrder;
use App\Modules\Stock\Reports\StockBalanceReport\Contracts\StockBalanceRepositoryInterface;
use App\Modules\Stock\Reports\StockBalanceReport\DataTransferObjects\StockBalanceFilterDTO;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

final class ValidateStockForManufacturingAction
{
    public function execute(StockSupplyOrder $order, Collection $allComponents): void
    {
        $requiredQuantities = [];

        foreach ($order->details as $detail) {
            $components = $allComponents->get($detail->product_id);

            if (! $components || $components->isEmpty()) {
                continue;
            }

            foreach ($components as $component) {
                $totalQtyToDeduct = $this->calculateRequiredQuantity(
                    (float) $component->quantity,
                    (float) $detail->quantity,
                    (float) ($component->qty_waste_percentage ?? 0)
                );

                if (! isset($requiredQuantities[$component->product_id])) {
                    $requiredQuantities[$component->product_id] = 0.0;
                }
                $requiredQuantities[$component->product_id] += $totalQtyToDeduct;
            }
        }

        if (empty($requiredQuantities)) {
            return;
        }

        /** @var StockBalanceRepositoryInterface $stockBalanceRepo */
        $stockBalanceRepo = app(StockBalanceRepositoryInterface::class);

        $filters = new StockBalanceFilterDTO(
            storeId: $order->store_id,
            productIds: array_keys($requiredQuantities)
        );

        // Fetch balances and index by ID for O(1) lookup performance
        $balances = $stockBalanceRepo->getBalances($filters)->keyBy('id');

        $shortages = [];

        foreach ($requiredQuantities as $productId => $requiredQty) {
            $balanceModel = $balances->get($productId);

            $availableQty = 0.0;
            $productName = "Product ID #{$productId}";

            if ($balanceModel) {
                // Best Practice & Performance: Calculate purely mathematically without JSON Resource overhead
                $availableQty = (float) ($balanceModel->total_in ?? 0) - (float) ($balanceModel->total_out ?? 0);
                $productName = $balanceModel->name ?? $productName;
            }

            // Optional safeguard against extreme precision issues
            if (round($availableQty, 6) < round($requiredQty, 6)) {
                $shortages[] = $productName;
            }
        }

        if (! empty($shortages)) {
            $count = count($shortages);
            $displayed = array_slice($shortages, 0, 2);
            $message = "Not enough stock for: " . implode(", ", $displayed);
            
            if ($count > 2) {
                $remaining = $count - 2;
                $message .= " (and {$remaining} more)";
            }

            throw ValidationException::withMessages(['components' => $message]);
        }
    }

    private function calculateRequiredQuantity(float $recipeQty, float $producedQty, float $wastePercentage): float
    {
        $baseRequiredQty = $recipeQty * $producedQty;

        return $baseRequiredQty * (1 + ($wastePercentage / 100));
    }
}
