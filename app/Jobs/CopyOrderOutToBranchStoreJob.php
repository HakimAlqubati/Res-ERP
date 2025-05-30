<?php

namespace App\Jobs;

use App\Services\CopyOrderOutToBranchStoreService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class CopyOrderOutToBranchStoreJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;



    public function handle(): void
    {
        // استدعاء الخدمة
        app(CopyOrderOutToBranchStoreService::class)->handle();
    }
}
