<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Jobs\AllocateFifoOutTransactionsJob;

class AllocateFifoOutTransactions extends Command
{
    /**
     * اسم الأمر لتشغيله من CLI
     */
    protected $signature = 'fifo:allocate-out';

    /**
     * وصف الأمر
     */
    protected $description = 'Apply FIFO logic and create OUT inventory transactions for orders and stock issue orders';

    /**
     * تنفيذ الأمر
     */
    public function handle(): void
    {
        $this->info('🚀 Starting FIFO OUT allocation job...');
        (new AllocateFifoOutTransactionsJob())->handle();
        // AllocateFifoOutTransactionsJob::dispatchSync(); // dispatch() لو أردتها في الطابور

        $this->info('✅ FIFO OUT allocation completed successfully.');
    }
}
