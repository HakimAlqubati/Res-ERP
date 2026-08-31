<?php

declare(strict_types=1);

namespace App\Modules\Stock\PurchaseReturns\Pipelines\Pipes;

use App\Models\InventoryTransaction;
use App\Models\PurchaseReturn;
use App\Modules\Stock\PurchaseReturns\DataTransferObjects\PurchaseReturnPipelineContext;
use Closure;

final class DeductInventoryStockPipe
{
    public function handle(PurchaseReturnPipelineContext $context, Closure $next)
    {
        $return = $context->purchaseReturn;

        foreach ($context->items as $item) {
            $notes = "Purchase Return #{$return->return_no} to Supplier ({$context->supplier?->name}) from Store ({$context->store?->name})";

            $sourceTxId = null;
            if ($context->purchaseInvoiceId) {
                $sourceTxId = InventoryTransaction::query()
                    ->where('transactionable_type', 'App\\Models\\PurchaseInvoice')
                    ->where('transactionable_id', $context->purchaseInvoiceId)
                    ->where('product_id', $item->productId)
                    ->where('unit_id', $item->unitId)
                    ->where('movement_type', InventoryTransaction::MOVEMENT_IN)
                    ->value('id');
            }

            InventoryTransaction::create([
                'product_id'            => $item->productId,
                'movement_type'         => InventoryTransaction::MOVEMENT_OUT,
                'quantity'              => $item->quantity,
                'unit_id'               => $item->unitId,
                'package_size'          => $item->packageSize,
                'price'                 => $item->unitPrice,
                'store_id'              => $context->storeId,
                'transaction_date'      => $context->returnDate,
                'movement_date'         => $context->returnDate . ' ' . now()->format('H:i:s'),
                'notes'                 => $notes,
                'transactionable_id'    => $return->id,
                'transactionable_type'  => PurchaseReturn::class,
                'source_transaction_id' => $sourceTxId,
            ]);
        }

        return $next($context);
    }
}
