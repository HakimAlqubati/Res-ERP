<?php

namespace App\Jobs;

use App\Services\ManufacturingBackfillService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ManufacturingBackfillJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected ?int $storeId;

    public function __construct(?int $storeId = null)
    {
        $this->storeId = $storeId;
    }

    public function handle(): void
    {
        Log::info('sdf',['sdf']);
        app(ManufacturingBackfillService::class)->handleFromSimulation($this->storeId);
    }
}