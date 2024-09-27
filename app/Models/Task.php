<?php

namespace App\Models;

use Filament\Facades\Filament;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;

class Task extends Model
{
    use SoftDeletes;

    protected $table = 'hr_tasks';

    const STATUS_NEW = 'new';
    const STATUS_PENDING = 'pending';
    const STATUS_IN_PROGRESS = 'in_progress';
    const STATUS_CLOSED = 'closed';

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
    ];

    public function assigned()
    {
        return $this->belongsTo(Employee::class, 'assigned_to');
    }
    public function createdby()
    {
        return $this->belongsTo(User::class, 'created_by');
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

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    protected static function booted()
    { 
        // parent::boot();
       
        if (auth()->check()) {
            if (isBranchManager()) {
                static::addGlobalScope(function (\Illuminate\Database\Eloquent\Builder $builder) {
                    $builder->where('branch_id', auth()->user()->branch_id); // Add your default query here
                });
            } elseif (isStuff()) {
                static::addGlobalScope(function (\Illuminate\Database\Eloquent\Builder $builder) {
                    $builder->where('assigned_to', auth()->user()->employee->id); // Add your default query here
                });
            }
        }
    }
}
