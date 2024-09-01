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
            $settings_up_daily_tasks = DailyTasksSettingUp::where('active', 1)->get();
            foreach ($settings_up_daily_tasks as $settings_up_daily_task) {
                $users = User::whereIn('id', json_decode($settings_up_daily_task->assigned_to_users))->get();
                $assigned_by = $settings_up_daily_task?->assigned_by;
                $title = $settings_up_daily_task?->title;
                $description = $settings_up_daily_task?->description;
                $menu_tasks = json_decode($settings_up_daily_task?->menu_tasks);

                foreach ($users as $user) {
                    $task = Task::create([
                        'title' => $title,
                        'assigned_to' => $user->id,
                        'created_by' => $assigned_by,
                        'updated_by' => $assigned_by,
                        'description' => $description,
                        'status_id' => TaskStatus::where('active', 1)?->first()?->id,
                        'due_date' => now()->endOfDay(),
                    ]);

                    // Create related TasksMenus records
                    if ($menu_tasks && is_array($menu_tasks)) {
                        foreach ($menu_tasks as $menu_task_id) {
                            $task->task_menu()->create([
                                'menu_task_id' => $menu_task_id,
                            ]);
                        }
                    }

                }
            }
            Log::info("Task created for user: {$user->name}");

        } catch (Exception $e) {
            // Handle exceptions
            Log::error("Failed to create tasks: " . $e->getMessage());
        }

    }
}
