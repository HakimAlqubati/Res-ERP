<?php

namespace App\Jobs;

use App\Services\Inventory\StockAdjustment\StockAdjustmentService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Auth;
use Throwable;

class ZeroStoreStockJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected int $storeId;
    protected ?int $reasonId;
    protected ?string $notes;
    protected ?int $userId;
    protected bool $forced;

    /**
     * Create a new job instance.
     */
    public function __construct(int $storeId, ?int $reasonId = null, ?string $notes = null, ?int $userId = null, bool $forced = true)
    {
        $this->storeId = $storeId;
        $this->reasonId = $reasonId;
        $this->notes = $notes;
        $this->userId = $userId;
        $this->forced = $forced;
    }

    /**
     * Execute the job.
     */
    public function handle(StockAdjustmentService $service): void
    {
        // If we need to spoof the user for audit trails (created_by)
        if ($this->userId) {
            Auth::loginUsingId($this->userId);
        }

        if ($this->forced) {
            $service->processZeroStoreStockDirect($this->storeId, $this->reasonId, $this->notes);
        } else {
            $service->processZeroStoreStock($this->storeId, $this->reasonId, $this->notes);
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(Throwable $exception): void
    {
        // You could log this to a specific table or send a notification to the user
    }
}
