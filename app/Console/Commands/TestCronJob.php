<?php

namespace App\Console\Commands;

use App\Models\AppLog;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class TestCronJob extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:cron';


    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'A test cron job that logs "Hi Hakim" every two minutes.';


    /**
     * Execute the console command.
     */
    public function handle()
    {
        AppLog::write('Hi Hakim from cron job');
    }
}
