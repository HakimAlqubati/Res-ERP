<?php

namespace App\Console\Commands;

use App\Services\CopyOrderOutToBranchStoreService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Spatie\Multitenancy\Models\Tenant;

class CopyOutToInForOrders extends Command
{
    protected $signature = 'orders:copy-out-to-in';

    protected $description = 'Copy OUT inventory transactions from orders into IN transactions for branch store if available.';

    public function handle()
    {
        
        $this->info('🚀 Starting to copy OUT transactions to IN for orders...');

        try {
            app(CopyOrderOutToBranchStoreService::class)->handle();
            $this->info('✅ Done copying inventory transactions.');
        } catch (\Throwable $e) {
            $this->error('❌ Error: ' . $e->getMessage());
        }
    }
}
