<?php

namespace App\Console\Commands;

use App\Services\Inventory\Summary\InventorySummaryRebuildService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class RebuildInventorySummaryCommand extends Command
{
    protected $signature = 'inventory:rebuild-summary 
                            {--store= : Rebuild for specific store ID}
                            {--product= : Rebuild for specific product ID}
                            {--step= : Run specific step only (1=generate, 2=calculate)}
                            {--dry-run : Show what would be done without making changes}';

    protected $description = 'Rebuild inventory summary table from transactions';

    public function handle(InventorySummaryRebuildService $service): int
    {
        $storeId = $this->option('store');
        $productId = $this->option('product');
        $step = $this->option('step');
        $dryRun = $this->option('dry-run');

        if ($dryRun) {
            $this->info('DRY RUN - No changes will be made');
        }

        $this->info('Starting inventory summary rebuild...');

        // Specific product/store
        if ($storeId && $productId) {
            if (!$dryRun) {
                $service->rebuildForStore((int) $storeId, (int) $productId);
            }
            $this->info("Rebuilt summary for store {$storeId}, product {$productId}");
            return Command::SUCCESS;
        }

        // Specific store only
        if ($storeId) {
            if (!$dryRun) {
                $count = $service->rebuildForStoreOnly((int) $storeId);
                $this->info("Rebuilt {$count} products for store {$storeId}");
            }
            return Command::SUCCESS;
        }

        // Step-by-step or full rebuild
        if ($step === '1') {
            // Step 1 only: Generate empty rows
            $this->info('Step 1: Generating empty rows for all products × units × stores...');
            if (!$dryRun) {
                $count = $service->generateEmptyRows();
                $this->info("Generated {$count} empty rows");
            }
        } elseif ($step === '2') {
            // Step 2 only: Calculate quantities
            $this->info('Step 2: Calculating quantities using OptimizedInventoryService...');
            if (!$dryRun) {
                $count = $service->calculateQuantities();
                $this->info("Updated {$count} rows with quantities");
            }
        } else {
            // Full rebuild (both steps)
            $this->info('Running full rebuild (Step 1 + Step 2)...');
            if (!$dryRun) {
                $result = $service->rebuildAll();
                $this->info("Step 1: Generated {$result['generated']} rows");
                $this->info("Step 2: Calculated {$result['calculated']} rows with inventory");
            } else {
                $productCount = DB::table('products')
                    ->whereExists(function ($query) {
                        $query->select(DB::raw(1))
                            ->from('unit_prices')
                            ->whereRaw('unit_prices.product_id = products.id');
                    })->count();
                $storeCount = DB::table('stores')->count();
                $avgUnits = DB::table('unit_prices')
                    ->select(DB::raw('AVG(cnt) as avg'))
                    ->fromSub(
                        DB::table('unit_prices')
                            ->select(DB::raw('COUNT(*) as cnt'))
                            ->groupBy('product_id'),
                        'counts'
                    )
                    ->value('avg') ?? 1;
                $estimated = (int) ($productCount * $storeCount * $avgUnits);
                $this->info("Would generate approximately {$estimated} rows");
            }
        }

        $this->info('Done!');
        return Command::SUCCESS;
    }
}
