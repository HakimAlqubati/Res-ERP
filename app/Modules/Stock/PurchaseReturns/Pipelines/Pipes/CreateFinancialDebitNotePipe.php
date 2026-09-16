<?php

declare(strict_types=1);

namespace App\Modules\Stock\PurchaseReturns\Pipelines\Pipes;

use App\Models\Branch;
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
            $branchId = Branch::where('store_id', $context->storeId)->value('id')
                ?? $context->store?->branch?->id
                ?? auth()->user()?->branch_id;

            $category = FinancialCategory::where('code', 'PURCHASE_RETURN')->first();
            if (! $category) {
                $category = FinancialCategory::where('type', FinancialTransaction::TYPE_INCOME)->first();
            }
            if (! $category) {
                $category = FinancialCategory::create([
                    'name'        => 'Purchase Returns / مردودات مشتريات',
                    'code'        => 'PURCHASE_RETURN',
                    'type'        => FinancialTransaction::TYPE_INCOME,
                    'is_system'   => true,
                    'is_visible'  => true,
                    'description' => 'Supplier debit notes and purchase return adjustments',
                ]);
            }

            // If a payment method is specified, it represents an immediate cash/bank refund.
            // Otherwise, it represents an on-account debit note / credit adjustment on supplier balance.
            $status = $context->paymentMethodId
                ? FinancialTransaction::STATUS_PAID
                : FinancialTransaction::STATUS_PENDING;

            FinancialTransaction::create([
                'branch_id'          => $branchId,
                'category_id'        => $category->id,
                'amount'             => $total,
                'type'               => FinancialTransaction::TYPE_INCOME,
                'transaction_date'   => $context->returnDate,
                'status'             => $status,
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
