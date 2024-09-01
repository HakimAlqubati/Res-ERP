<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Task extends Model
{
    use SoftDeletes;

    protected $table = 'hr_tasks';

    protected $fillable = [
        'title',
        'description',
        'assigned_to',
        'status_id',
        'created_by',
        'updated_by',
        'due_date',
        'menu_tasks',
    ];

    public function status()
    {
        return $this->belongsTo(TaskStatus::class, 'status_id');
    }

    public function assigned()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }
    public function createdby()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function comments()
    {
        return $this->hasOne(TaskComment::class, 'task_id');
    }

    public function attachments()
    {
        return $this->hasOne(TaskAttachment::class, 'task_id');
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
}
