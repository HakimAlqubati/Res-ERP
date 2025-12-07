<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\InventoryTransaction;
use App\Models\Order;
use Illuminate\Support\Facades\DB;

class FixInventoryMovementDates extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fix:inventory-dates {--tenant_id=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Updates inventory_transactions movement_date and transaction_date based on Order transfer_date. Optional: --tenant_id to run for specific tenant.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $tenantId = $this->option('tenant_id');

        if ($tenantId) {
            $tenant = \App\Models\CustomTenantModel::find($tenantId);
            if (! $tenant) {
                $this->error("Tenant with ID {$tenantId} not found.");
                return;
            }

            $this->info("Switching to tenant: {$tenant->name} ({$tenant->database})");
            $tenant->makeCurrent();
            $this->runUpdate();
        } else {
            $this->info("Running on default connection (no tenant specified)...");
            $this->runUpdate();
        }
    }

    private function runUpdate()
    {
        try {
            // Query Builder approach for Model Query
            $affected = InventoryTransaction::query()
                ->join('orders', 'inventory_transactions.transactionable_id', '=', 'orders.id')
                ->where('inventory_transactions.transactionable_type', Order::class)
                ->where('inventory_transactions.movement_type', InventoryTransaction::MOVEMENT_IN)
                ->where('inventory_transactions.store_id',10)
                ->whereNotNull('orders.transfer_date')
                // Only update if dates are different to avoid unnecessary writes
                ->where(function ($query) {
                    $query->whereColumn('inventory_transactions.movement_date', '!=', 'orders.transfer_date')
                        ->orWhereColumn('inventory_transactions.transaction_date', '!=', 'orders.transfer_date');
                })
                ->update([
                    'inventory_transactions.movement_date'    => DB::raw('orders.transfer_date'),
                    'inventory_transactions.transaction_date' => DB::raw('orders.transfer_date'),
                ]);

            $this->info("  - Updated {$affected} rows.");
        } catch (\Exception $e) {
            $this->error("  - Error updating records: " . $e->getMessage());
        }
    }
}
