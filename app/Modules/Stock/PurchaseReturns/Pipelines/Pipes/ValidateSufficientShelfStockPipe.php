<?php

declare(strict_types=1);

namespace App\Modules\Stock\PurchaseReturns\Pipelines\Pipes;

use App\Models\Product;
use App\Modules\Stock\PurchaseReturns\DataTransferObjects\PurchaseReturnPipelineContext;
use App\Modules\Stock\PurchaseReturns\Exceptions\InsufficientShelfStockException;
use App\Services\MultiProductsInventoryService;
use Closure;

final class ValidateSufficientShelfStockPipe
{
    public function handle(PurchaseReturnPipelineContext $context, Closure $next)
    {
        // Aggregate requested quantities per product and unit
        $aggregatedQuantities = [];

        foreach ($context->items as $item) {
            $key = "{$item->productId}_{$item->unitId}";
            $aggregatedQuantities[$key] = ($aggregatedQuantities[$key] ?? [
                'product_id' => $item->productId,
                'unit_id'    => $item->unitId,
                'quantity'   => 0.0,
            ]);
            $aggregatedQuantities[$key]['quantity'] += $item->quantity;
        }

        foreach ($aggregatedQuantities as $data) {
            $productId = (int) $data['product_id'];
            $unitId    = (int) $data['unit_id'];
            $totalQty  = (float) $data['quantity'];

            $availableQty = MultiProductsInventoryService::getRemainingQty(
                $productId,
                $unitId,
                $context->storeId
            );

            if ($availableQty < $totalQty) {
                $product = Product::find($productId);
                $productName = $product?->name ?? "Product #{$productId}";
                $storeName = $context->store?->name ?? "Store #{$context->storeId}";

                throw new InsufficientShelfStockException(
                    "Insufficient shelf stock for [{$productName}] in [{$storeName}]. Available on shelf: {$availableQty}, Requested return: {$totalQty}. Please perform a stock adjustment if needed."
                );
            }
        }

        return $next($context);
    }
}
