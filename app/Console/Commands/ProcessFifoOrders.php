<?php

namespace App\Console\Commands;

use Exception;
use App\Services\FixFifo\FifoAllocatorService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ProcessFifoOrders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:process-fifo-orders';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Execute FIFO allocation for ready/delivered orders';


    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🚀 Starting FIFO allocation...');

        try {
            $service = new FifoAllocatorService();
            $allocations = $service->allocateForOrders();

            Log::alert('hi', [count($allocations)]);
            // طباعة ملخص أو عدد العمليات
            $this->info("✅ Allocation complete. Total allocations: " . count($allocations));
        } catch (Exception $e) {
            $this->error('❌ Error: ' . $e->getMessage());
        }

        return Command::SUCCESS;
    }
}
