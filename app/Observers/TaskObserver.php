<?php

namespace App\Observers;

use App\Models\Task;
use App\Models\TaskLog;
use Filament\Notifications\Actions\Action;
use Filament\Notifications\Notification;

class TaskObserver
{
    /**
     * Handle the Task "created" event.
     */
    public function created(Task $task): void
    {
      // Check if the task is not daily
      if ($task->is_daily == 0) {
        // Fetch the assigned user
        $assignedUser = $task?->assigned?->user;

         // Automatically create a log entry for the task creation
         $task->createLog(
            createdBy: $task->created_by ?? auth()->id(), // Set the creator, fallback to authenticated user
            description: 'Task created',
            logType: TaskLog::TYPE_CREATED,
            details: [
                'title' => $task->title,
                'description' => $task->description,
                'assigned_to' => $task->assigned_to,
                'due_date' => $task->due_date,
            ]
        );
        
        if($assignedUser){
            // Send a notification if the user exists
            if ($assignedUser) {
                Notification::make()
                    ->title('New Task Assigned')
                    ->body("A new task titled '{$task->title}' has been assigned to you.")
                    ->success()
                    ->actions([

                        Action::make('view')->label('View')
                            ->button()
                            ->url(route('filament.admin.h-r-tasks-system.resources.tasks.index', $task), shouldOpenInNewTab: true),
                       
                    ])
                    ->sendToDatabase($assignedUser)
                    ->toBroadcast();
            }
        }
    }
    }

    /**
     * Handle the Task "updated" event.
     */
    public function updated(Task $task): void
    {
        //
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
