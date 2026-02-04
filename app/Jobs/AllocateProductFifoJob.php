<?php

namespace App\Jobs;

use App\Services\FixFifo\FifoAllocationSaver;
use App\Services\FixFifo\FifoAllocatorService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class AllocateProductFifoJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $productId;

    /**
     * Create a new job instance.
     */
    public function __construct($productId)
    {
        $this->productId = $productId;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $fifoService = new FifoAllocatorService();

        try {
            $allocations = $fifoService->allocate($this->productId);

            if (!empty($allocations)) {
                FifoAllocationSaver::save($allocations, $this->productId);
                Log::info("✅ Allocation completed for product_id: {$this->productId}");
            }
        } catch (Throwable $e) {
            Log::error("❌ Error allocating product_id={$this->productId}", [
                'error' => $e->getMessage(),
            ]);
            // Optional: fail the job so it can be retried or inspected in failed_jobs
            $this->fail($e);
        }
    }
}
