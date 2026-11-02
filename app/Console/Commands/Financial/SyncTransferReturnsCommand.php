<?php

namespace App\Console\Commands\Financial;

use App\Services\Financial\ReturnOrderFinancialSyncService;
use Illuminate\Console\Command;

class SyncTransferReturnsCommand extends Command
{
    protected $signature = 'financial:sync-transfer-returns
                            {--branch= : Specific branch ID to sync}
                            {--month= : Filter by month (1-12)}
                            {--year= : Filter by year}
                            {--start-date= : Start date (Y-m-d)}
                            {--end-date= : End date (Y-m-d)}
                            {--force : Force update existing financial transactions}
                            {--all : Sync all branches}';

    protected $description = 'Sync approved returned transfer orders to financial transactions (TRANSFER_RETURNS)';

    protected ReturnOrderFinancialSyncService $syncService;

    public function __construct(ReturnOrderFinancialSyncService $syncService)
    {
        parent::__construct();
        $this->syncService = $syncService;
    }

    public function handle(): int
    {
        $this->info('🚀 Starting Transfer Returns Financial Sync...');
        $this->newLine();

        $options = $this->buildOptions();

        if ($this->option('all')) {
            $this->syncAllBranches($options);
        } elseif ($branchId = $this->option('branch')) {
            $this->syncSingleBranch((int) $branchId, $options);
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

        if ($this->option('force')) {
            $options['force'] = true;
        }

        return $options;
    }

    protected function syncSingleBranch(int $branchId, array $options): void
    {
        $this->info("📦 Syncing transfer returns for Branch ID: {$branchId}");

        $result = $this->syncService->syncReturnsForBranch($branchId, $options);

        if (!$result['success']) {
            $this->error("❌ {$result['message']}");
            return;
        }

        $this->displayResult($result);
    }

    protected function syncAllBranches(array $options): void
    {
        $this->info('📦 Syncing transfer returns for ALL branches...');

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
            $this->warn('⚠️  Some errors occurred. Use --verbose for details.');
        }

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

    protected function displayResult(array $result): void
    {
        $this->newLine();
        $this->info("✅ {$result['message']}");

        $this->table(
            ['Metric', 'Count'],
            [
                ['Total Returned Orders', $result['total_returns']],
                ['Synced', $result['synced']],
                ['Skipped (Already Exists)', $result['skipped']],
                ['Errors', $result['errors']],
            ]
        );

        if (!empty($result['error_details'])) {
            $this->newLine();
            $this->warn('⚠️  Errors encountered:');
            foreach ($result['error_details'] as $error) {
                $this->line("  • ReturnedOrder #{$error['returned_order_id']}: {$error['error']}");
            }
        }
    }
}
