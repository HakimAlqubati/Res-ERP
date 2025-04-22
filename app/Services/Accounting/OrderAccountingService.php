<?php

namespace App\Services\Accounting;

use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use App\Models\Order;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class OrderAccountingService
{
    /**
     * Creates a journal entry for an order when it's marked as READY_FOR_DELIVERY
     */
    public static function createJournalEntryForDeliveredOrder(Order $order): ?JournalEntry
    {
        if ($order->status !== Order::READY_FOR_DELEVIRY || $order->cancelled || $order->orderDetails->isEmpty()) {
            return null;
        }

        $totalCost = $order->orderDetails->sum(fn($d) => $d->total_price);

        $inventoryAccountId = $order->store?->inventory_account_id;
        $branchAccountId = $order->branch?->operational_cost_account_id;

        if (!$inventoryAccountId || !$branchAccountId) {
            Log::warning("⚠️ Missing accounting IDs for order #{$order->id}. Inventory: {$inventoryAccountId}, Branch: {$branchAccountId}");
            return null;
        }

        DB::beginTransaction();

        try {
            $entry = JournalEntry::create([
                'date' => $order->order_date ?? now(),
                'description' => "Order Delivery #{$order->id}",
                'related_model_id' => $order->id,
                'related_model_type' => Order::class,
            ]);

            // Debit: Cost of delivery to expense account (branch)
            JournalEntryLine::create([
                'journal_entry_id' => $entry->id,
                'account_id' => $branchAccountId,
                'debit' => $totalCost,
                'credit' => 0,
                'description' => "Cost of goods delivered to branch: {$order->branch?->name}",
            ]);

            // Credit: Inventory decrease from warehouse/store
            JournalEntryLine::create([
                'journal_entry_id' => $entry->id,
                'account_id' => $inventoryAccountId,
                'debit' => 0,
                'credit' => $totalCost,
                'description' => "Inventory issued from store: {$order->store?->name}",
            ]);

            DB::commit();
            return $entry;

        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error("❌ Failed to create journal entry for Order #{$order->id}: " . $e->getMessage());
            return null;
        }
    }
}
