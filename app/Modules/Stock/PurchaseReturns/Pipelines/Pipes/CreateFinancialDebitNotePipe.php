<?php

declare(strict_types=1);

namespace App\Modules\Stock\PurchaseReturns\Pipelines\Pipes;

use App\Models\FinancialCategory;
use App\Models\FinancialTransaction;
use App\Models\PurchaseReturn;
use App\Modules\Stock\PurchaseReturns\DataTransferObjects\PurchaseReturnPipelineContext;
use Closure;

final class CreateFinancialDebitNotePipe
{
    public function handle(PurchaseReturnPipelineContext $context, Closure $next)
    {
        $return = $context->purchaseReturn;
        $total = (float) $return->total_amount;

        if ($total > 0) {
            $categoryId = FinancialCategory::where('code', 'PURCHASE_RETURN')->value('id');

            FinancialTransaction::create([
                'branch_id'          => $context->store?->branch_id,
                'category_id'        => $categoryId,
                'amount'             => $total,
                'type'               => FinancialTransaction::TYPE_INCOME,
                'transaction_date'   => $context->returnDate,
                'status'             => FinancialTransaction::STATUS_PAID,
                'payment_method_id'  => $context->paymentMethodId,
                'description'        => "Debit Note for Purchase Return #{$return->return_no} to Supplier {$context->supplier?->name}",
                'reference_type'     => PurchaseReturn::class,
                'reference_id'       => $return->id,
                'created_by'         => $context->userId,
            ]);
        }

        return $next($context);
    }
}
