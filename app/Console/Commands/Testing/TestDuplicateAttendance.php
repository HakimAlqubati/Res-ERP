<?php

namespace App\Console\Commands\Testing;

use Illuminate\Console\Command;
use Symfony\Component\Process\Process;
use App\Modules\HR\Attendance\Services\AttendanceService;

class TestDuplicateAttendance extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:duplicate-attendance {employee_id=14} {--count=2} {--child} {--date=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Tests duplicate attendance by invoking the service concurrently in separate processes';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        if ($this->option('child')) {
            $this->runChild();
            return 0;
        }

        $this->runParent();
        return 0;
    }

    protected function runChild()
    {
        try {
            /** @var AttendanceService $service */
            $service = app(AttendanceService::class);

            // Use provided date or default
            $date = $this->option('date') ?: '2026-01-31 14:00:50';

            $data = [
                'employee_id' => $this->argument('employee_id'),
                'date_time' => $date,
                'type' => 'checkin', // Explicitly setting type
                'attendance_type' => 'request',
            ];

            // Call the service directly
            $result = $service->handle($data);

            // Convert result to array to check status
            $resArray = method_exists($result, 'toArray') ? $result->toArray() : (array)$result;

            if (isset($resArray['success']) && $resArray['success'] === true) {
                $this->info("RESULT: SUCCESS");
            } else {
                $msg = $resArray['message'] ?? 'Unknown Error';
                $this->error("RESULT: FAILED ($msg)");
            }
        } catch (\Throwable $e) {
            $this->error("RESULT: ERROR " . $e->getMessage());
        }
    }

    protected function runParent()
    {
        $count = (int)$this->option('count');
        $id = $this->argument('employee_id');
        // Use a fixed timestamp to ensure collision
        $date = '2026-01-31 14:00:50';

        $this->info("Starting $count concurrent processes for Employee $id at $date...");
        $this->info("Note: This directly boots Laravel in child processes (no HTTP).");

        $processes = [];
        for ($i = 0; $i < $count; $i++) {
            // Spawn a new PHP process running this same command with --child
            $cmd = [
                'php',
                'artisan',
                'test:duplicate-attendance',
                $id,
                '--child',
                "--date=$date"
            ];

            $process = new Process($cmd);
            $process->setWorkingDirectory(base_path());
            $process->start();
            $processes[] = $process;
        }

        $this->info("Waiting for processes to finish...");

        $successCount = 0;
        $outputs = [];

        foreach ($processes as $index => $process) {
            $process->wait();

            $output = $process->getOutput() . $process->getErrorOutput();
            $outputs[] = "Process $index: " . str_replace(["\r", "\n"], " ", trim($output));

            if (str_contains($output, 'RESULT: SUCCESS')) {
                $successCount++;
            }
        }

        $this->newLine();
        foreach ($outputs as $out) {
            $this->line($out);
        }
        $this->newLine();

        if ($successCount > 1) {
            $this->error("TEST FAILED: Race condition occurred! $successCount requests succeeded.");
        } else {
            $this->info("TEST PASSED: Concurrency handled. Only $successCount request(s) succeeded.");
        }
    }
}
