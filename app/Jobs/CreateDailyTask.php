<?php

namespace App\Jobs;

use App\Models\DailyTasksSettingUp;
use App\Models\Task;
use App\Models\TaskStatus;
use App\Models\User;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class CreateDailyTask implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {

    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            $settings_up_daily_tasks = DailyTasksSettingUp::where('active',1)->first();
            $users = User::whereIn('id', json_decode($settings_up_daily_tasks->assigned_to_users))->get();
            $assigned_by = $settings_up_daily_tasks?->assigned_by;
            $title = $settings_up_daily_tasks?->title;
            $description = $settings_up_daily_tasks?->description;
            foreach ($users as $user) {
                Task::create([
                    'title' => $title,
                    'assigned_to' => $user->id,
                    'created_by' => $assigned_by,
                    'updated_by' => $assigned_by,
                    'description' => $description,
                    'status_id' => TaskStatus::where('active', 1)?->first()?->id,
                    'due_date' => now()->endOfDay(),
                ]);

            }
            Log::info("Task created for user: {$user->name}");

        } catch (Exception $e) {
            // Handle exceptions
            Log::error("Failed to create tasks: " . $e->getMessage());
        }

    }
}
