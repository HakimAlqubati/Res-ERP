<?php

declare(strict_types=1);

namespace App\Modules\Stock\PurchaseReturns\Pipelines\Pipes;

use App\Models\PurchaseReturn;
use App\Models\PurchaseReturnDetail;
use App\Modules\Stock\PurchaseReturns\DataTransferObjects\PurchaseReturnPipelineContext;
use Closure;

final class PersistPurchaseReturnRecordPipe
{
    public function handle(PurchaseReturnPipelineContext $context, Closure $next)
    {
        $totalAmount = $context->calculateTotalAmount();

        if ($context->purchaseReturn) {
            $return = $context->purchaseReturn;
            $return->update([
                'return_date'         => $context->returnDate,
                'supplier_id'         => $context->supplierId,
                'store_id'            => $context->storeId,
                'payment_method_id'   => $context->paymentMethodId,
                'total_amount'        => $totalAmount,
                'reason'              => $context->reason,
                'notes'               => $context->notes,
                'attachment'          => $context->attachment ?? $return->attachment,
            ]);
            $return->details()->delete();
        } else {
            $return = PurchaseReturn::create([
                'return_no'           => PurchaseReturn::autoReturnNo(),
                'return_date'         => $context->returnDate,
                'purchase_invoice_id' => $context->purchaseInvoiceId,
                'supplier_id'         => $context->supplierId,
                'store_id'            => $context->storeId,
                'payment_method_id'   => $context->paymentMethodId,
                'status'              => PurchaseReturn::STATUS_DRAFT,
                'total_amount'        => $totalAmount,
                'reason'              => $context->reason,
                'notes'               => $context->notes,
                'attachment'          => $context->attachment,
                'created_by'          => $context->userId,
            ]);
            $context->purchaseReturn = $return;
        }

        foreach ($context->items as $item) {
            PurchaseReturnDetail::create([
                'purchase_return_id'         => $return->id,
                'purchase_invoice_detail_id' => $item->purchaseInvoiceDetailId,
                'product_id'                 => $item->productId,
                'unit_id'                    => $item->unitId,
                'package_size'               => $item->packageSize,
                'quantity'                   => $item->quantity,
                'unit_price'                 => $item->unitPrice,
                'total_price'                => $item->getTotalPrice(),
                'notes'                      => $item->notes,
            ]);
        }

        return $next($context);
    }
}
