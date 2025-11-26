<?php

namespace App\Console\Commands\Financial;

use App\Services\Financial\TransferFinancialSyncService;
use Illuminate\Console\Command;

class SyncTransfersCommand extends Command
{
    protected $signature = 'financial:sync-transfers
                            {--branch= : Specific branch ID to sync}
                            {--month= : Filter by month (1-12)}
                            {--year= : Filter by year}
                            {--start-date= : Start date (Y-m-d)}
                            {--end-date= : End date (Y-m-d)}
                            {--all : Sync all branches}';

    protected $description = 'Sync transfer orders to financial transactions';

    protected TransferFinancialSyncService $syncService;

    public function __construct(TransferFinancialSyncService $syncService)
    {
        parent::__construct();
        $this->syncService = $syncService;
    }

    public function handle()
    {
        $this->info('🚀 Starting Transfer Financial Sync...');
        $this->newLine();

        $options = $this->buildOptions();

        if ($this->option('all')) {
            $this->syncAllBranches($options);
        } elseif ($branchId = $this->option('branch')) {
            $this->syncSingleBranch($branchId, $options);
        } else {
            $this->error('❌ Please specify either --branch=ID or --all');
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }

    protected function buildOptions(): array
    {
        $options = [];

        if ($month = $this->option('month')) {
            $options['month'] = $month;
        }

        if ($year = $this->option('year')) {
            $options['year'] = $year;
        }

        if ($startDate = $this->option('start-date')) {
            $options['start_date'] = $startDate;
        }

        if ($endDate = $this->option('end-date')) {
            $options['end_date'] = $endDate;
        }

        return $options;
    }

    protected function syncSingleBranch(int $branchId, array $options)
    {
        $this->info("📦 Syncing transfers for Branch ID: {$branchId}");

        $result = $this->syncService->syncTransfersForBranch($branchId, $options);

        if (!$result['success']) {
            $this->error("❌ {$result['message']}");
            return;
        }

        $this->displayResult($result);
    }

    protected function syncAllBranches(array $options)
    {
        $this->info('📦 Syncing transfers for ALL branches...');

        $result = $this->syncService->syncAllBranches($options);

        $this->newLine();
        $this->info("✅ {$result['message']}");
        $this->table(
            ['Metric', 'Count'],
            [
                ['Total Branches', $result['total_branches']],
                ['Synced', $result['total_synced']],
                ['Skipped', $result['total_skipped']],
                ['Errors', $result['total_errors']],
            ]
        );

        if ($result['total_errors'] > 0) {
            $this->newLine();
            $this->warn('⚠️  Some errors occurred. Check the logs for details.');
        }

        // Display per-branch summary
        if ($this->option('verbose')) {
            $this->newLine();
            $this->info('📊 Per-Branch Results:');
            foreach ($result['branch_results'] as $branchResult) {
                if ($branchResult['success']) {
                    $this->line("  • {$branchResult['branch_name']}: {$branchResult['synced']} synced, {$branchResult['skipped']} skipped, {$branchResult['errors']} errors");
                }
            }
        }
    }

    protected function displayResult(array $result)
    {
        $this->newLine();
        $this->info("✅ {$result['message']}");

        $this->table(
            ['Metric', 'Count'],
            [
                ['Total Orders', $result['total_orders']],
                ['Synced', $result['synced']],
                ['Skipped (Already Exists)', $result['skipped']],
                ['Errors', $result['errors']],
            ]
        );

        if (!empty($result['error_details'])) {
            $this->newLine();
            $this->warn('⚠️  Errors encountered:');
            foreach ($result['error_details'] as $error) {
                $this->line("  • Order #{$error['order_id']}: {$error['error']}");
            }
        }
    }
}
