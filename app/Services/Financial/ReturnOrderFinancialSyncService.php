<?php

namespace App\Services\Financial;

use App\Enums\FinancialCategoryCode;
use App\Models\Branch;
use App\Models\FinancialCategory;
use App\Models\FinancialTransaction;
use App\Models\InventoryTransaction;
use App\Models\Order;
use App\Models\ReturnedOrder;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ReturnOrderFinancialSyncService
{
    /**
     * Sync approved returned orders to financial transactions for a specific branch.
     *
     * @param int $branchId
     * @param array $options Additional filters (e.g., month, year, start_date, end_date, force)
     * @return array Summary of the sync operation
     */
    public function syncReturnsForBranch(int $branchId, array $options = []): array
    {
        $branch = Branch::withoutGlobalScopes()->find($branchId);

        if (!$branch) {
            return [
                'success' => false,
                'message' => "Branch with ID {$branchId} not found.",
                'synced' => 0,
                'skipped' => 0,
                'errors' => 0,
            ];
        }

        $returnCategory = $this->getOrCreateReturnCategory();

        if (!$returnCategory) {
            return [
                'success' => false,
                'message' => 'Transfer Returns financial category not found.',
                'synced' => 0,
                'skipped' => 0,
                'errors' => 0,
            ];
        }

        $query = ReturnedOrder::withoutGlobalScopes()
            ->where('branch_id', $branchId)
            ->where('status', ReturnedOrder::STATUS_APPROVED)
            ->whereNull('deleted_at')
            ->whereHas('branch', function ($q) {
                $q->withoutGlobalScopes()->where('type', '!=', Branch::TYPE_RESELLER);
            });

        if (isset($options['month']) && isset($options['year'])) {
            $query->whereMonth('returned_date', $options['month'])
                ->whereYear('returned_date', $options['year']);
        }

        if (isset($options['start_date']) && isset($options['end_date'])) {
            $query->whereBetween('returned_date', [$options['start_date'], $options['end_date']]);
        }

        $returnedOrders = $query->with(['details', 'order'])->get();

        $synced = 0;
        $skipped = 0;
        $errors = 0;
        $errorDetails = [];
        $forceUpdate = (bool) ($options['force'] ?? false);

        foreach ($returnedOrders as $returnedOrder) {
            $returnedOrder->setRelation('branch', $branch);
            $result = $this->syncReturnedOrder($returnedOrder, $returnCategory, $forceUpdate);

            if ($result['status'] === 'synced') {
                $synced++;
            } elseif ($result['status'] === 'skipped') {
                $skipped++;
            } elseif ($result['status'] === 'error') {
                $errors++;
                $errorDetails[] = [
                    'returned_order_id' => $returnedOrder->id,
                    'error' => $result['message'],
                ];
            }
        }

        return [
            'success' => true,
            'message' => "Transfer returns sync completed for branch: {$branch->name}",
            'branch_id' => $branchId,
            'branch_name' => $branch->name,
            'total_returns' => $returnedOrders->count(),
            'synced' => $synced,
            'skipped' => $skipped,
            'errors' => $errors,
            'error_details' => $errorDetails,
        ];
    }

    /**
     * Sync a single returned order to financial transactions.
     *
     * @param ReturnedOrder $returnedOrder
     * @param FinancialCategory|null $returnCategory
     * @param bool $forceUpdate
     * @return array
     */
    public function syncReturnedOrder(
        ReturnedOrder $returnedOrder,
        ?FinancialCategory $returnCategory = null,
        bool $forceUpdate = false
    ): array {
        try {
            if ($returnedOrder->status !== ReturnedOrder::STATUS_APPROVED) {
                return [
                    'success' => false,
                    'status' => 'error',
                    'message' => 'Returned order must be approved before syncing to financial transactions.',
                ];
            }

            $branch = $returnedOrder->relationLoaded('branch')
                ? $returnedOrder->branch
                : Branch::withoutGlobalScopes()->find($returnedOrder->branch_id);

            if (!$branch || $branch->type === Branch::TYPE_RESELLER) {
                return [
                    'success' => true,
                    'status' => 'skipped',
                    'message' => 'Branch is missing or is a reseller branch.',
                ];
            }

            $returnedOrder->setRelation('branch', $branch);

            $returnCategory ??= $this->getOrCreateReturnCategory();

            if (!$returnCategory) {
                return [
                    'success' => false,
                    'status' => 'error',
                    'message' => 'Transfer Returns financial category not found.',
                ];
            }

            $existingTransaction = FinancialTransaction::where('reference_type', ReturnedOrder::class)
                ->where('reference_id', $returnedOrder->id)
                ->first();

            if ($existingTransaction && !$forceUpdate) {
                return [
                    'success' => true,
                    'status' => 'skipped',
                    'message' => 'Financial transaction already exists for this returned order.',
                ];
            }

            $costAmount = $this->calculateReturnCostForBranch($returnedOrder);

            if ($costAmount <= 0) {
                return [
                    'success' => true,
                    'status' => 'skipped',
                    'message' => 'Calculated return cost amount is zero or null.',
                ];
            }

            $dateString = $returnedOrder->getRawOriginal('returned_date')
                ?? ($returnedOrder->returned_date ? Carbon::parse($returnedOrder->returned_date)->toDateString() : null)
                ?? $returnedOrder->created_at?->toDateString()
                ?? now()->toDateString();

            $date = Carbon::parse($dateString);

            DB::transaction(function () use ($returnedOrder, $branch, $returnCategory, $costAmount, $date) {
                FinancialTransaction::updateOrCreate(
                    [
                        'reference_type' => ReturnedOrder::class,
                        'reference_id' => $returnedOrder->id,
                        'category_id' => $returnCategory->id,
                    ],
                    [
                        'branch_id' => $returnedOrder->branch_id,
                        'amount' => $costAmount,
                        'type' => FinancialTransaction::TYPE_INCOME,
                        'transaction_date' => $date->toDateString(),
                        'status' => FinancialTransaction::STATUS_PAID,
                        'description' => "Transfer Return #{$returnedOrder->id} for Order #{$returnedOrder->original_order_id} from {$branch->name}",
                        'created_by' => $returnedOrder->approved_by ?? $returnedOrder->created_by ?? auth()->id() ?? 1,
                        'month' => $date->month,
                        'year' => $date->year,
                    ]
                );
            });

            return [
                'success' => true,
                'status' => 'synced',
                'message' => 'Transfer return financial transaction synced successfully.',
                'amount' => $costAmount,
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'status' => 'error',
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Calculate the exact financial return cost to credit back to the branch.
     * Uses the branch store's MOVEMENT_OUT inventory transactions (which carry the branch transfer price + markup).
     */
    public function calculateReturnCostForBranch(ReturnedOrder $returnedOrder): float
    {
        $branchStoreId = $returnedOrder->branch?->store_id
            ?? Branch::withoutGlobalScopes()->where('id', $returnedOrder->branch_id)->value('store_id');

        $outQuery = InventoryTransaction::query()
            ->where('transactionable_type', ReturnedOrder::class)
            ->where('transactionable_id', $returnedOrder->id)
            ->where('movement_type', InventoryTransaction::MOVEMENT_OUT)
            ->whereNull('deleted_at');

        if ($branchStoreId) {
            $outQuery->where('store_id', $branchStoreId);
        }

        $inventoryOutCost = (float) $outQuery->sum(DB::raw('quantity * price'));

        if ($inventoryOutCost > 0) {
            return round($inventoryOutCost, 2);
        }

        // Fallback: calculate from ReturnedOrderDetails using original Order's MOVEMENT_IN price in branch store
        $returnedOrder->loadMissing(['details', 'order']);
        $fallbackCost = 0.0;

        foreach ($returnedOrder->details as $detail) {
            $sourceTransaction = InventoryTransaction::query()
                ->where('transactionable_type', Order::class)
                ->where('transactionable_id', $returnedOrder->original_order_id)
                ->where('product_id', $detail->product_id)
                ->where('unit_id', $detail->unit_id)
                ->where('movement_type', InventoryTransaction::MOVEMENT_IN)
                ->when($branchStoreId, fn ($q) => $q->where('store_id', $branchStoreId))
                ->whereNull('deleted_at')
                ->first();

            $unitPrice = $sourceTransaction ? (float) $sourceTransaction->price : (float) $detail->price;
            $fallbackCost += ((float) $detail->quantity) * $unitPrice;
        }

        return round($fallbackCost, 2);
    }

    /**
     * Sync transfer returns for all active non-reseller branches.
     *
     * @param array $options
     * @return array
     */
    public function syncAllBranches(array $options = []): array
    {
        $branches = Branch::withoutGlobalScopes()
            ->where('active', 1)
            ->where('type', '!=', Branch::TYPE_RESELLER)
            ->get();

        $results = [];
        $totalSynced = 0;
        $totalSkipped = 0;
        $totalErrors = 0;

        foreach ($branches as $branch) {
            $result = $this->syncReturnsForBranch($branch->id, $options);
            $results[] = $result;

            if ($result['success']) {
                $totalSynced += $result['synced'];
                $totalSkipped += $result['skipped'];
                $totalErrors += $result['errors'];
            }
        }

        return [
            'success' => true,
            'message' => 'Transfer returns sync completed for all branches',
            'total_branches' => $branches->count(),
            'total_synced' => $totalSynced,
            'total_skipped' => $totalSkipped,
            'total_errors' => $totalErrors,
            'branch_results' => $results,
        ];
    }

    /**
     * Delete financial transaction linked to a returned order.
     */
    public function deleteReturnedOrderTransaction(ReturnedOrder $returnedOrder): bool
    {
        return (bool) FinancialTransaction::where('reference_type', ReturnedOrder::class)
            ->where('reference_id', $returnedOrder->id)
            ->delete();
    }

    /**
     * Get or create the Transfer Returns financial category.
     */
    protected function getOrCreateReturnCategory(): ?FinancialCategory
    {
        return FinancialCategory::findByCode(FinancialCategoryCode::TRANSFER_RETURNS)
            ?? FinancialCategory::firstOrCreate(
                ['code' => FinancialCategoryCode::TRANSFER_RETURNS],
                [
                    'name' => 'Transfer Returns',
                    'type' => FinancialCategory::TYPE_INCOME,
                    'profit_type' => FinancialCategory::PROFIT_TYPE_GROSS,
                    'description' => 'Returns from branch transfers',
                    'is_system' => true,
                    'is_visible' => true,
                ]
            );
    }
}
