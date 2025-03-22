<?php

namespace App\Console\Commands;

use App\Http\Controllers\TestController;
use App\Models\DailyTasksSettingUp;
use Carbon\Carbon;
use Illuminate\Console\Command;

class ScheduleTask extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    // protected $signature = 'app:schedule-task';
    protected $signature = 'app:schedule-task {--date=}';


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
        // $currentDate = now()->toDateString();
        // $dayName = date('l', strtotime($currentDate));

        // Get the date from the command argument or use the current date
        $inputDate = $this->option('date');
        $currentDate = $inputDate ? Carbon::parse($inputDate)->toDateString() : now()->toDateString();

        // Get the day name from the date
        $dayName = Carbon::parse($currentDate)->format('l');

        $testController = new TestController();
        $testController->to_test_schedule_task($currentDate);
        $this->info('Custom task executed successfully! ' . $currentDate . '   ' . $dayName);
    }
}
