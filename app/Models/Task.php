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
    const COLOR_PENDING = 'warning';
    const COLOR_IN_PROGRESS = 'info';
    const COLOR_CLOSED = 'success';

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
                    self::STATUS_PENDING => 'Pending',
                ];
            case self::STATUS_PENDING:
                return [
                    self::STATUS_IN_PROGRESS => 'In Progress',
                ];
            case self::STATUS_IN_PROGRESS:
                return [
                    self::STATUS_CLOSED => 'Closed',
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

    public function task_menu()
    {
        return $this->hasMany(TasksMenus::class, 'task_id');
    }

    public function menus()
    {
        return $this->belongsToMany(TasksMenu::class, 'hr_tasks_menus')
        // ->withPivot('price')
        ;
    }

    // Define the relationship to TasksMenu through the TasksMenus pivot table
    public function taskMenus()
    {
        return $this->hasManyThrough(TasksMenu::class, TasksMenus::class, 'task_id', 'id', 'id', 'menu_task_id');
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
                    ->orWhere('assigned_by',auth()->user()->id)->orWhere('created_by',auth()->user()->id)
                    ; // Add your default query here
                });
            }
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
        if ($logType === TaskLog::TYPE_MOVED) {
            // Get the last "moved" log for this task, ordered by creation time
            $lastMovedLog = $this->logs()
                ->where('log_type', TaskLog::TYPE_MOVED)
                ->latest()
                ->first();
// dd($lastMovedLog->created_at,$lastMovedLog);
            if ($lastMovedLog) {
                // Calculate the difference in hours and minutes
                $timeDifference = now()->diff($lastMovedLog->created_at);
                $totalHoursTaken = $timeDifference->format('%H:%I:%S');
            }
        }
// dd($totalHoursTaken,$lastMovedLog->created_at);
        // Create the log entry
        return $this->logs()->create([
            'created_by' => $createdBy,
            'description' => $description,
            'log_type' => $logType,
            'details' => $details ? json_encode($details) : null,
            'total_hours_taken' => $totalHoursTaken,
        ]);
    }
}
