<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Attendance;
use App\Models\AttendanceImagesUploaded;
use Carbon\Carbon;

class LinkAttendanceImagesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'attendance:link-images {--days=30 : Number of days look back} {--force : Force update existing links}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Link uploaded attendance images to attendance records based on time proximity';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $days = (int) $this->option('days');
        $force = (bool) $this->option('force');

        Attendance::query()
            ->where('employee_id', 18)
            ->update([
                'source_id' => null,
                'source_type' => null,
            ]);
        $this->info("Starting linkage process for the last {$days} days...");

        $attendanceQuery = Attendance::query()
            ->where('employee_id', 18)

            // ->where('check_date', '<', '2026-02-23')
        ;

        if (!$force) {
            $attendanceQuery->whereNull('source_id');
        }

        $count = $attendanceQuery
            ->count();
        $bar = $this->output->createProgressBar($count);
        $updatedCount = 0;

        $attendanceQuery->chunk(100, function ($attendances) use ($bar, &$updatedCount) {
            foreach ($attendances as $attendance) {
                try {
                    // Combine date and time to get full timestamp (use real_check_date to handle night shifts)
                    $actualDate = $attendance->real_check_date
                        ?: $attendance->check_date;
                    $attendanceTimestamp = Carbon::parse($actualDate . ' ' . $attendance->check_time);

                    // Logic based on AttendanceContext.php:
                    // Range: [Time - 2 mins, Time + 1 min buffer]

                    // Revert to strict 15-minute window to catch any syncing issues on the external database
                    $startTime = $attendanceTimestamp->copy()->subMinutes(2);
                    $endTime = $attendanceTimestamp->copy()->addMinutes(2);

                    // We need to use raw query mostly because datetime in DB might be string
                    // But Eloquent whereBetween usually handles it.
                    // Let's debug by getting ALL images for this employee around this time

                    $image = AttendanceImagesUploaded::query()
                        ->where('employee_id', $attendance->employee_id)
                        ->whereBetween('datetime', [$startTime, $endTime])
                        // Get the one with minimum time difference
                        ->get()
                        ->sortBy(fn($img) => abs(Carbon::parse($img->datetime)->diffInSeconds($attendanceTimestamp)))
                        ->first();

                    if ($image) {
                        $attendance->update([
                            'source_type' => AttendanceImagesUploaded::class,
                            'source_id'   => $image->id,
                        ]);
                        $updatedCount++;
                    }
                } catch (\Exception $e) {
                    // $this->error("Error linking attendance #{$attendance->id}: " . $e->getMessage());
                }
                $bar->advance();
            }
        });

        $bar->finish();
        $this->newLine();
        $this->info("Linking process completed. Updated records: {$updatedCount}");

        return $updatedCount;
    }
}
