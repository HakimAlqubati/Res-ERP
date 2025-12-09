<?php

namespace App\Services\Financial;

use App\Enums\FinancialCategoryCode;
use App\Models\Branch;
use App\Models\FinancialCategory;
use App\Models\FinancialTransaction;
use App\Models\Order;
use App\Services\Orders\OrderCostAnalysisService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TransferFinancialSyncService
{
    protected OrderCostAnalysisService $costAnalysisService;

    public function __construct(OrderCostAnalysisService $costAnalysisService)
    {
        $this->costAnalysisService = $costAnalysisService;
    }

    /**
     * Sync transfer orders to financial transactions for a specific branch.
     *
     * @param int $branchId
     * @param array $options Additional filters (e.g., month, year, date range)
     * @return array Summary of the sync operation
     */
    public function syncTransfersForBranch(int $branchId, array $options = []): array
    {
        $branch = Branch::find($branchId);

        if (!$branch) {
            return [
                'success' => false,
                'message' => "Branch with ID {$branchId} not found.",
                'synced' => 0,
                'skipped' => 0,
                'errors' => 0,
            ];
        }

        // Get the Transfers financial category
        $transferCategory = FinancialCategory::findByCode(FinancialCategoryCode::TRANSFERS);

        if (!$transferCategory) {
            return [
                'success' => false,
                'message' => 'Transfers financial category not found. Please run the seeder first.',
                'synced' => 0,
                'skipped' => 0,
                'errors' => 0,
            ];
        }

        // Build query for transfer orders
        $query = Order::query()
            ->where('branch_id', $branchId)
            ->whereIn('status', [Order::READY_FOR_DELEVIRY, Order::DELEVIRED])
            ->where('cancelled', false)
            ->whereHas('branch', function ($q) {
                $q->where('type', '!=', Branch::TYPE_RESELLER);
            });

        // Apply optional filters
        if (isset($options['month']) && isset($options['year'])) {
            $query->whereMonth('transfer_date', $options['month'])
                ->whereYear('transfer_date', $options['year']);
        }

        if (isset($options['start_date']) && isset($options['end_date'])) {
            $query->whereBetween('transfer_date', [$options['start_date'], $options['end_date']]);
        }

        $orders = $query->get();

        $synced = 0;
        $skipped = 0;
        $errors = 0;
        $errorDetails = [];

        foreach ($orders as $order) {
            $result = $this->syncOrder($order, $transferCategory);

            if ($result['status'] === 'synced') {
                $synced++;
            } elseif ($result['status'] === 'skipped') {
                $skipped++;
            } elseif ($result['status'] === 'error') {
                $errors++;
                $errorDetails[] = [
                    'order_id' => $order->id,
                    'error' => $result['message'],
                ];
            }
        }

        return [
            'success' => true,
            'message' => "Sync completed for branch: {$branch->name}",
            'branch_id' => $branchId,
            'branch_name' => $branch->name,
            'total_orders' => $orders->count(),
            'synced' => $synced,
            'skipped' => $skipped,
            'errors' => $errors,
            'error_details' => $errorDetails,
        ];
    }

    /**
     * Sync a single order to financial transactions.
     *
     * @param Order $order
     * @param FinancialCategory|null $transferCategory
     * @return array
     */
    public function syncOrder(Order $order, ?FinancialCategory $transferCategory = null): array
    {
        try {
            if (!$transferCategory) {
                $transferCategory = FinancialCategory::findByCode(FinancialCategoryCode::TRANSFERS);
            }

            if (!$transferCategory) {
                return [
                    'success' => false,
                    'status' => 'error',
                    'message' => 'Transfers financial category not found.',
                ];
            }

            // Check if financial transaction already exists for this order
            $existingTransaction = FinancialTransaction::where('reference_type', Order::class)
                ->where('reference_id', $order->id)
                ->exists();

            if ($existingTransaction) {
                return [
                    'success' => true,
                    'status' => 'skipped',
                    'message' => 'Transaction already exists.',
                ];
            }

            // Get cost analysis for this order
            $analysis = $this->costAnalysisService->getOrderValues($order->id);

            // Check if analysis was successful
            if ($analysis['status'] === 'Error' || !isset($analysis['total_cost_from_inventory_transactions'])) {
                return [
                    'success' => false,
                    'status' => 'error',
                    'message' => $analysis['message'] ?? 'Unknown error',
                ];
            }

            $costAmount = $analysis['total_cost_from_inventory_transactions'];

            // Skip if cost is zero or null
            if (!$costAmount || $costAmount <= 0) {
                return [
                    'success' => true,
                    'status' => 'skipped',
                    'message' => 'Cost amount is zero or null.',
                ];
            }

            // Create financial transaction
            DB::transaction(function () use ($order, $transferCategory, $costAmount) {
                FinancialTransaction::create([
                    'branch_id' => $order->branch_id,
                    'category_id' => $transferCategory->id,
                    'amount' => $costAmount,
                    'type' => FinancialTransaction::TYPE_EXPENSE,
                    'transaction_date' => $order->transfer_date,
                    'status' => FinancialTransaction::STATUS_PAID,
                    'description' => "Transfer for Order #{$order->id} to {$order->branch->name}",
                    'reference_type' => Order::class,
                    'reference_id' => $order->id,
                    'created_by' => $order->customer_id ?? auth()->id() ?? 1,
                    'month' => $order->transfer_date ? date('m', strtotime($order->transfer_date)) : date('m'),
                    'year' => $order->transfer_date ? date('Y', strtotime($order->transfer_date)) : date('Y'),
                ]);
            });

            return [
                'success' => true,
                'status' => 'synced',
                'message' => 'Transaction created successfully.',
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
     * Sync transfers for all branches.
     *
     * @param array $options
     * @return array
     */
    public function syncAllBranches(array $options = []): array
    {
        $branches = Branch::where('active', 1)
            ->where('type', '!=', Branch::TYPE_RESELLER)
            ->get();

        $results = [];
        $totalSynced = 0;
        $totalSkipped = 0;
        $totalErrors = 0;

        foreach ($branches as $branch) {
            $result = $this->syncTransfersForBranch($branch->id, $options);
            $results[] = $result;

            if ($result['success']) {
                $totalSynced += $result['synced'];
                $totalSkipped += $result['skipped'];
                $totalErrors += $result['errors'];
            }
        }

        return [
            'success' => true,
            'message' => 'Sync completed for all branches',
            'total_branches' => $branches->count(),
            'total_synced' => $totalSynced,
            'total_skipped' => $totalSkipped,
            'total_errors' => $totalErrors,
            'branch_results' => $results,
        ];
    }
}
