<?php

declare(strict_types=1);

namespace App\Modules\Stock\PurchaseReturns\Actions;

use App\Models\PurchaseReturn;
use App\Modules\Stock\PurchaseReturns\DataTransferObjects\CreatePurchaseReturnDTO;
use App\Modules\Stock\PurchaseReturns\DataTransferObjects\PurchaseReturnPipelineContext;
use App\Modules\Stock\PurchaseReturns\Pipelines\Pipes\PersistPurchaseReturnRecordPipe;
use App\Modules\Stock\PurchaseReturns\Pipelines\Pipes\ValidateInvoiceEligibilityPipe;
use App\Modules\Stock\PurchaseReturns\Pipelines\Pipes\ValidateQuantityNotExceedingInvoicePipe;
use Illuminate\Pipeline\Pipeline;
use Illuminate\Support\Facades\DB;

final class CreatePurchaseReturnDraftAction
{
    public function __construct(
        private readonly Pipeline $pipeline
    ) {}

    public function execute(CreatePurchaseReturnDTO $dto): PurchaseReturn
    {
        $existing = $dto->returnId ? PurchaseReturn::find($dto->returnId) : null;

        $context = new PurchaseReturnPipelineContext(
            purchaseInvoiceId: $dto->purchaseInvoiceId,
            supplierId: $dto->supplierId,
            storeId: $dto->storeId,
            returnDate: $dto->returnDate,
            userId: $dto->userId,
            items: $dto->items,
            reason: $dto->reason,
            notes: $dto->notes,
            attachment: $dto->attachment,
            paymentMethodId: $dto->paymentMethodId,
            existingReturn: $existing,
        );

        return DB::transaction(function () use ($context) {
            $this->pipeline
                ->send($context)
                ->through([
                    ValidateInvoiceEligibilityPipe::class,
                    ValidateQuantityNotExceedingInvoicePipe::class,
                    PersistPurchaseReturnRecordPipe::class,
                ])
                ->then(fn(PurchaseReturnPipelineContext $ctx) => $ctx->purchaseReturn);

            return $context->purchaseReturn->fresh(['details', 'supplier', 'store']);
        });
    }
}
