<?php

declare(strict_types=1);

namespace App\Modules\Stock\PurchaseReturns\Actions;

use App\Models\PurchaseReturn;
use App\Modules\Stock\PurchaseReturns\DataTransferObjects\PurchaseReturnItemDTO;
use App\Modules\Stock\PurchaseReturns\DataTransferObjects\PurchaseReturnPipelineContext;
use App\Modules\Stock\PurchaseReturns\Exceptions\PurchaseReturnValidationException;
use App\Modules\Stock\PurchaseReturns\Pipelines\Pipes\CreateFinancialDebitNotePipe;
use App\Modules\Stock\PurchaseReturns\Pipelines\Pipes\DeductInventoryStockPipe;
use App\Modules\Stock\PurchaseReturns\Pipelines\Pipes\ValidateInvoiceEligibilityPipe;
use App\Modules\Stock\PurchaseReturns\Pipelines\Pipes\ValidateQuantityNotExceedingInvoicePipe;
use App\Modules\Stock\PurchaseReturns\Pipelines\Pipes\ValidateSufficientShelfStockPipe;
use Illuminate\Pipeline\Pipeline;
use Illuminate\Support\Facades\DB;

final class ApprovePurchaseReturnAction
{
    public function __construct(
        private readonly Pipeline $pipeline
    ) {}

    public function execute(PurchaseReturn $purchaseReturn, int $approverId): PurchaseReturn
    {
        return DB::transaction(function () use ($purchaseReturn, $approverId) {
            // Lock the return row to prevent race condition / concurrent double approval
            $lockedReturn = PurchaseReturn::where('id', $purchaseReturn->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedReturn->status === PurchaseReturn::STATUS_APPROVED) {
                throw new PurchaseReturnValidationException('This purchase return is already approved.');
            }

            if ($lockedReturn->cancelled) {
                throw new PurchaseReturnValidationException('Cannot approve a cancelled purchase return.');
            }

            $items = $lockedReturn->details->map(fn($d) => new PurchaseReturnItemDTO(
                productId: (int) $d->product_id,
                unitId: (int) $d->unit_id,
                quantity: (float) $d->quantity,
                unitPrice: (float) $d->unit_price,
                purchaseInvoiceDetailId: $d->purchase_invoice_detail_id,
                packageSize: (float) $d->package_size,
                notes: $d->notes
            ))->toArray();

            $context = new PurchaseReturnPipelineContext(
                purchaseInvoiceId: $lockedReturn->purchase_invoice_id,
                supplierId: (int) $lockedReturn->supplier_id,
                storeId: (int) $lockedReturn->store_id,
                returnDate: $lockedReturn->return_date ? $lockedReturn->return_date->format('Y-m-d') : date('Y-m-d'),
                userId: $approverId,
                items: $items,
                reason: $lockedReturn->reason,
                notes: $lockedReturn->notes,
                attachment: $lockedReturn->attachment,
                paymentMethodId: $lockedReturn->payment_method_id,
                existingReturn: $lockedReturn
            );

            $this->pipeline
                ->send($context)
                ->through([
                    ValidateInvoiceEligibilityPipe::class,
                    ValidateQuantityNotExceedingInvoicePipe::class,
                    ValidateSufficientShelfStockPipe::class,
                    DeductInventoryStockPipe::class,
                    CreateFinancialDebitNotePipe::class,
                ])
                ->then(function (PurchaseReturnPipelineContext $ctx) use ($approverId) {
                    $ctx->purchaseReturn->update([
                        'status'      => PurchaseReturn::STATUS_APPROVED,
                        'approved_by' => $approverId,
                        'approved_at' => now(),
                    ]);
                });

            return $lockedReturn->fresh(['details', 'supplier', 'store']);
        });
    }
}
