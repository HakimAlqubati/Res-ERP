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
        foreach ($context->items as $item) {
            if ($item->quantity <= 0) {
                throw new InsufficientShelfStockException('Return quantity must be greater than zero.');
            }

            $availableQty = MultiProductsInventoryService::getRemainingQty(
                $item->productId,
                $item->unitId,
                $context->storeId
            );

            if ($availableQty < $item->quantity) {
                $product = Product::find($item->productId);
                $productName = $product?->name ?? "Product #{$item->productId}";
                $storeName = $context->store?->name ?? "Store #{$context->storeId}";

                throw new InsufficientShelfStockException(
                    "Insufficient shelf stock for [{$productName}] in [{$storeName}]. Available on shelf: {$availableQty}, Requested return: {$item->quantity}. Please perform a stock adjustment if needed."
                );
            }
        }

        return $next($context);
    }
}
