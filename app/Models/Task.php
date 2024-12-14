<?php

namespace App\Models;

use Filament\Facades\Filament;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class Task extends Model
{
    use SoftDeletes;

    protected $table = 'hr_tasks';

    const STATUS_NEW = 'new';
    const STATUS_PENDING = 'pending';
    const STATUS_IN_PROGRESS = 'in_progress';
    const STATUS_CLOSED = 'closed';

    const STATUS_REJECTED = 'rejected';

    const COLOR_NEW = 'primary';
    const COLOR_REJECTED = 'danger';
    const COLOR_PENDING = 'warning';
    const COLOR_IN_PROGRESS = 'info';
    const COLOR_CLOSED = 'success';

    const ICON_NEW = 'heroicon-o-plus-circle';
    const ICON_REJECTED = 'heroicon-m-backspace';
    const ICON_PENDING = 'heroicon-o-clock';
    const ICON_IN_PROGRESS = 'heroicon-o-arrow-right-circle';
    const ICON_CLOSED = 'heroicon-o-check-circle';
    protected $fillable = [
        'title',
        'description',
        'assigned_to',
        'assigned_by',
        'task_status',
        'created_by',
        'updated_by',
        'due_date',
        'menu_tasks',
        'is_daily',
        'start_date',
        'end_date',
        'schedule_type',
        'branch_id',
        'views',
    ];


    /**
     * Get possible next statuses based on the current status
     *
     * @return array
     */
    public function getNextStatuses()
    {
        switch ($this->task_status) {
            case self::STATUS_NEW:
                return [
                    // self::STATUS_PENDING => 'Pending',
                    self::STATUS_IN_PROGRESS => 'In Progress',
                ];
                // case self::STATUS_PENDING:
                //     return [
                //         self::STATUS_IN_PROGRESS => 'In Progress',
                //     ];
            case self::STATUS_IN_PROGRESS:
                return [
                    self::STATUS_CLOSED => 'Closed',
                ];
            case self::STATUS_REJECTED:
                return [
                    self::STATUS_IN_PROGRESS => 'In progress',
                ];
            default:
                return []; // No transitions available for final statuses
        }
    }

    public function assigned()
    {
        return $this->belongsTo(Employee::class, 'assigned_to');
    }
    public function createdby()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
    public function assignedby()
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }

    public function comments()
    {
        return $this->hasMany(TaskComment::class, 'task_id');
    }

    public function photos()
    {
        return $this->hasMany(TaskAttachment::class, 'task_id');
    }

    public function getPhotosCountAttribute()
    {
        return $this->photos()->count();
    }
    public function task_rating()
    {
        return $this->hasOne(TaskRating::class, 'task_id');
    }



    // Add this array to map all statuses for easier usage
    public static function getStatuses()
    {
        return [
            self::STATUS_NEW,
            self::STATUS_PENDING,
            self::STATUS_IN_PROGRESS,
            self::STATUS_CLOSED,
        ];
    }

    // Method to get statuses excluding specific ones
    public static function getStatusesExcluding(array $excludeStatuses = [])
    {
        return array_filter(self::getStatuses(), function ($status) use ($excludeStatuses) {
            return !in_array($status, $excludeStatuses);
        });
    }

    public static function getStatusColors()
    {
        return [
            self::STATUS_PENDING => self::COLOR_PENDING,
            self::STATUS_NEW => self::COLOR_NEW,
            self::STATUS_IN_PROGRESS => self::COLOR_IN_PROGRESS,
            self::STATUS_CLOSED => self::COLOR_CLOSED,
        ];
    }

    // You can also add a scope to filter tasks by status
    public function scopeStatus($query, $status)
    {
        return $query->where('task_status', $status);
    }

    public function steps()
    {
        // return $this->hasMany(TaskStep::class,'task_id');
        return $this->morphMany(TaskStep::class, 'morphable');
    }

    public function getStepCountAttribute()
    {
        return $this->steps?->count();
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    protected static function booted()
    {
        // parent::boot();

        //    dd(auth()->user(),auth()->user()->has_employee,auth()->user()->employee);
        if (auth()->check()) {
            if (isBranchManager()) {
                static::addGlobalScope(function (\Illuminate\Database\Eloquent\Builder $builder) {
                    $builder->where('branch_id', auth()->user()->branch_id); // Add your default query here
                });
            } elseif (isStuff()) {
                static::addGlobalScope(function (\Illuminate\Database\Eloquent\Builder $builder) {
                    $builder->where('assigned_to', auth()->user()->employee->id); // Add your default query here
                });
            } elseif (isFinanceManager()) {
                static::addGlobalScope(function (\Illuminate\Database\Eloquent\Builder $builder) {
                    $builder->where('assigned_to', auth()->user()->employee->id)
                        ->orWhere('assigned_by', auth()->user()->id)->orWhere('created_by', auth()->user()->id)
                    ; // Add your default query here
                });
            }
            static::addGlobalScope(function (\Illuminate\Database\Eloquent\Builder $builder) {
                $builder->where('is_daily', 0);
            });
        }
    }


    /**
     * Create a new task.
     *
     * @param array $data
     * @return Task|null
     */
    public static function createTask(array $data)
    {
        // Start a database transaction
        // DB::beginTransaction();

        try {
            // Create and return the new task
            $task = self::create($data);

            // Commit the transaction
            // DB::commit();

            return $task;
        } catch (\Exception $e) {
            // Rollback the transaction if something goes wrong
            // DB::rollBack();

            // Log the error or handle it as needed
            Log::error('Task creation failed: ' . $e->getMessage());

            // Optionally, you could throw the exception again or return null
            return null; // Indicate failure
        }
    }

    // Define a hasMany relationship with TaskLog
    public function logs()
    {
        return $this->hasMany(TaskLog::class, 'task_id');
    }


    /**
     * Create a log entry for the task.
     *
     * @param int $createdBy - The ID of the user who created the log
     * @param string $description - Description of the log entry
     * @param string $logType - Type of the log entry (e.g., 'created', 'moved')
     * @param array|null $details - Optional details as an array
     * @return TaskLog
     */
    public function createLog(int $createdBy, string $description, string $logType, array $details = null)
    {
        // Ensure that the log type is valid
        $validLogTypes = [
            TaskLog::TYPE_CREATED,
            TaskLog::TYPE_MOVED,
            TaskLog::TYPE_EDITED,
            TaskLog::TYPE_REJECTED,
            TaskLog::TYPE_COMMENTED,
            TaskLog::TYPE_IMAGES_ADDED,
        ];

        if (!in_array($logType, $validLogTypes)) {
            throw new \InvalidArgumentException("Invalid log type: {$logType}");
        }

        // Check if this is a "moved" log to calculate time difference
        $totalHoursTaken = null;
        // if ($logType === TaskLog::TYPE_MOVED || $logType == TaskLog::TYPE_REJECTED) {
        if (in_array($logType, [TaskLog::TYPE_MOVED, TaskLog::TYPE_REJECTED]) && $this->status != Task::STATUS_CLOSED) {
            // Get the last "moved" log for this task, ordered by creation time
            $lastMovedLog = $this->logs()
                ->where(function ($query) {
                    $query->where('log_type', TaskLog::TYPE_MOVED)
                        ->orWhere('log_type', TaskLog::TYPE_REJECTED);
                })
                ->latest()
                ->first();

            if ($lastMovedLog) {
                // Calculate the difference in hours and minutes
                $timeDifference = now()->diff($lastMovedLog->created_at);
                $totalHoursTaken = $timeDifference->format('%H:%I:%S');


                // Update the total_hours_taken field of the previous log
                $lastMovedLog->update([
                    'total_hours_taken' => $totalHoursTaken,
                ]);
            }
        }

        // Create the log entry
        return $this->logs()->create([
            'created_by' => $createdBy,
            'description' => $description,
            'log_type' => $logType,
            'details' => $details ? json_encode($details) : null,
            'total_hours_taken' => $totalHoursTaken,
        ]);
    }

    public static function canReject()
    {
        if (isBranchManager()) {
            return true;
        }
        return false;
    }

    // Accessor to check if all steps for the task are done
    public function getIsAllDoneAttribute(): bool
    {
        // Check if all related TaskSteps are marked as done
        return $this->steps()->where('done', false)->count() === 0;
    }


    public function getTotalSpentTimeAttribute(): string
    {
        $totalSeconds = 0;

        // Loop through each TaskLog and accumulate time in seconds
        foreach ($this->logs as $log) {

            $details =  json_decode($log->details, true);
            // if (is_array($details) && array_key_exists('to', $details) && $details['to'] != Task::STATUS_CLOSED) {
            //     continue;
            // }

            // Skip if the task is closed
            if (is_array($details) && array_key_exists('to', $details) && $details['to'] === Task::STATUS_CLOSED) {
                continue;
            }
            if (in_array($log->log_type, [TaskLog::TYPE_MOVED, TaskLog::TYPE_REJECTED])) {

                if ($log->total_hours_taken) {
                    // Convert each time entry from HH:MM:SS format to seconds
                    list($hours, $minutes, $seconds) = explode(':', $log->total_hours_taken);
                    $totalSeconds += ($hours * 3600) + ($minutes * 60) + $seconds;
                }
            }
        }


        // Calculate days, hours, minutes, and seconds
        $days = intdiv($totalSeconds, 86400);
        $totalSeconds %= 86400;
        $hours = intdiv($totalSeconds, 3600);
        $totalSeconds %= 3600;
        $minutes = intdiv($totalSeconds, 60);
        $seconds = $totalSeconds % 60;

        // Format as d h m s
        $formattedTime = '';
        if ($days > 0) {
            $formattedTime .= sprintf("%dd ", $days);
        }
        if ($hours > 0 || $days > 0) {
            $formattedTime .= sprintf("%dh ", $hours);
        }
        if ($minutes > 0 || $hours > 0 || $days > 0) {
            $formattedTime .= sprintf("%dm ", $minutes);
        }
        $formattedTime .= sprintf("%ds", $seconds);

        return trim($formattedTime);
    }

    public function getMoveCountAttribute(): int
    {
        // Count the number of logs where log_type is TYPE_MOVED
        return $this->logs()->where('log_type', TaskLog::TYPE_MOVED)->count();
    }
    public function getRejectionCountAttribute(): int
    {
        // Count the number of logs where log_type is TYPE_MOVED
        return $this->logs()->where('log_type', TaskLog::TYPE_REJECTED)->count();
    }
    public static function countMovesToStatus(int $taskId, string $status): int
    {
        return TaskLog::where('task_id', $taskId)
            ->where('log_type', TaskLog::TYPE_MOVED)
            ->whereJsonContains('details->to', $status)
            ->count();
    }

    public function taskCards()
    {
        return $this->hasMany(TaskCard::class, 'task_id');
    }

    /**
     * to get progress percentage of the task
     */
    public function getProgressPercentageAttribute(): float
    {
        // Count the total number of steps
        $totalSteps = $this->steps()->count();

        // Count the number of completed steps (is_done = true)
        $completedSteps = $this->steps()->where('is_done', true)->count();

        // Calculate the percentage of completed steps
        if ($totalSteps > 0) {
            return ($completedSteps / $totalSteps) * 100;
        }

        return 0; // If there are no steps, progress is 0%
    }
}
