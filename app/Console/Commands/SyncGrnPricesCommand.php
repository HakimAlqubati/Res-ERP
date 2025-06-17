<?php

namespace App\Console\Commands;

use App\Services\GrnPriceSyncService;
use Illuminate\Console\Command;

class SyncGrnPricesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'grn:sync-prices';


    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle(GrnPriceSyncService $syncService)
    {
        $this->info('⏳ Starting GRN price sync...');
        $syncService->syncAllGrnPrices();
        $this->info('✅ GRN prices synced successfully.');
    }
}
