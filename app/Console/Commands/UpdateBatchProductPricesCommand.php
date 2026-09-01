<?php

namespace App\Console\Commands;

use App\Services\Products\UpdateBatchProductPricesService;
use Illuminate\Console\Command;
use Spatie\Multitenancy\Models\Tenant;

class UpdateBatchProductPricesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'products:update-batch-prices {--tenant= : Optional tenant ID to run the command under}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update prices for products (311-001 to 311-016) in unit_prices and inventory_transactions tables';

    /**
     * Execute the console command.
     */
    public function handle(UpdateBatchProductPricesService $service): int
    {
        $tenantId = $this->option('tenant');

        if ($tenantId && class_exists(Tenant::class)) {
            $tenant = Tenant::find($tenantId);

            if (!$tenant) {
                $this->error("❌ Tenant with ID [{$tenantId}] not found.");
                return self::FAILURE;
            }

            $tenant->makeCurrent();
            $this->info("🏢 Switched to Tenant: [{$tenant->name} (ID: {$tenant->id})]");
        } else {
            $this->info("🌐 Executing in current tenant / default context.");
        }

        $this->info("🚀 Starting batch product prices update...");

        try {
            $report = $service->execute();

            $this->newLine();
            $this->info("📊 --- Execution Summary ---");
            $this->info("✔ Products Processed: " . $report['products_processed']);
            $this->info("✔ Unit Prices Updated: " . $report['unit_prices_updated']);
            $this->info("✔ Inventory Transactions Updated: " . $report['transactions_updated']);

            if (!empty($report['details'])) {
                $this->newLine();
                $this->table(
                    ['Product Code', 'Product Name', 'Unit', 'Old Price', 'New Price', 'Transactions Updated'],
                    array_map(function ($row) {
                        return [
                            $row['product_code'],
                            $row['product_name'],
                            $row['unit'],
                            number_format($row['old_price'], 2),
                            number_format($row['new_price'], 2),
                            $row['transactions_updated'],
                        ];
                    }, $report['details'])
                );
            }

            if (!empty($report['warnings'])) {
                $this->newLine();
                $this->warn("⚠️ Warnings encountered during update:");
                foreach ($report['warnings'] as $warning) {
                    $this->warn(" - {$warning}");
                }
            }

            $this->newLine();
            $this->info("✅ Product prices update completed successfully!");
            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->error("❌ Error updating prices: " . $e->getMessage());
            return self::FAILURE;
        }
    }
}
