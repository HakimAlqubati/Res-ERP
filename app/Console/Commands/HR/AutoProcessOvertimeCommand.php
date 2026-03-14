<?php

namespace App\Console\Commands\HR;

use App\Modules\HR\Overtime\OvertimeService;
use Illuminate\Console\Command;
use Carbon\Carbon;

class AutoProcessOvertimeCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'hr:overtime:auto-process {date?} {--branch_id=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Automatically process suggested overtime for employees based on attendance records.';

    /**
     * Execute the console command.
     *
     * @param OvertimeService $overtimeService
     * @return int
     */
    public function handle(OvertimeService $overtimeService)
    {
        $date = $this->argument('date') ?: Carbon::yesterday()->toDateString();
        $branchId = $this->option('branch_id');

        $this->info("Starting automatic overtime processing for date: {$date}" . ($branchId ? " (Branch ID: {$branchId})" : ""));

        $results = $overtimeService->autoProcessSuggestedOvertime($date, $branchId);

        if (empty($results)) {
            $this->warn("No processing results found or no active branches.");
            return 0;
        }

        $headers = ['Branch', 'Processed Records / Status'];
        $data = [];
        foreach ($results as $branchName => $status) {
            $data[] = [$branchName, $status];
        }

        $this->table($headers, $data);
        $this->info("Automatic overtime processing completed.");

        return 0;
    }
}
