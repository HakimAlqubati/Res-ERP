<?php

namespace App\Console;

use App\Console\Commands\TestCronJob;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use Illuminate\Support\Facades\Log;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        $schedule->command('inspire')->hourly();
        $schedule->command('app:schedule-task')->dailyAt('06:00');
        $schedule->command('report:send-absent-employees')->dailyAt('10:00');
        $schedule->call('test:cron')->everyMinute();
        
        // $schedule->job(new TestCronJob)->everyTwentySeconds();
        // $schedule->call(function () {
        //     Log::info('Scheduler is working');
        // })->everyMinute();
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
