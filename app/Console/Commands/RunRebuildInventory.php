<?php

namespace App\Console\Commands;

use App\Jobs\RebuildInventoryFromSources;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class RunRebuildInventory extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'inventory:rebuild-from-sources';
    protected $description = 'Dispatch job to rebuild inventory from invoices, GRNs, and supply orders.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        Log::info('✅ start run of rebuild.');
        $this->info('📦 Dispatching job to rebuild inventory...');
        // dispatch(new RebuildInventoryFromSources());
        (new RebuildInventoryFromSources())->handle();

        $this->info('✅ Job dispatched successfully!');
    }
}
