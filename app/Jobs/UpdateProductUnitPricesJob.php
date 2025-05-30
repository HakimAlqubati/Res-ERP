<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Console\Commands\UpdateProductUnitPrices;
use Illuminate\Support\Facades\Artisan;

class UpdateProductUnitPricesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected ?int $tenantId;

    public function __construct(?int $tenantId = null)
    {
        $this->tenantId = $tenantId;
    }

    public function handle(): void
    {
        // شغّل أمر الكونسول مباشرة
        Artisan::call('products:update-unit-prices', [
            '--tenant' => $this->tenantId,
        ]);
    }
}
