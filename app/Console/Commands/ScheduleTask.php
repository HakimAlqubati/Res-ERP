<?php

namespace App\Console\Commands;

use App\Models\DailyTasksSettingUp;
use Illuminate\Console\Command;

class ScheduleTask extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:schedule-task';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $currentDate = '2024-09-25';
        $dayName = date('l', strtotime($currentDate));
        $scheduleTasks = DailyTasksSettingUp::where('active',1)->whereDateBetween('start_date');
        $this->info('Custom task executed successfully! '. $currentDate . '   ' . $dayName);
    }
}
