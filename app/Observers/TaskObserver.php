<?php

namespace App\Observers;

use App\Models\DailyTasksSettingUp;
use App\Models\Task;

class TaskObserver
{
    /**
     * Handle the Task "created" event.
     */
    public function created(Task $task): void
    {  
      
    }

    /**
     * Handle the Task "updated" event.
     */
    public function updated(Task $task): void
    {
        // dd($task->steps);

        // $dailyTask = DailyTasksSettingUp::find(5);
        //   foreach ($task->steps as $step) {
        //     $dailyTask->steps()->create([
        //         'title' => $step->title,
        //         'order' => $step->order,
        //     ]);
        // }
    }

    /**
     * Handle the Task "deleted" event.
     */
    public function deleted(Task $task): void
    {
        //
    }

    /**
     * Handle the Task "restored" event.
     */
    public function restored(Task $task): void
    {
        //
    }

    /**
     * Handle the Task "force deleted" event.
     */
    public function forceDeleted(Task $task): void
    {
        //
    }
}
