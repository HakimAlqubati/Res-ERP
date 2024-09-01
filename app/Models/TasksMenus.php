<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TasksMenus extends Model
{
    use HasFactory;
    protected $table = 'hr_tasks_menus';
    protected $fillable = ['task_id', 'menu_task_id', 'status', 'done'];

    // Define relationships to both Task and TasksMenu
    public function task()
    {
        return $this->belongsTo(Task::class, 'task_id');
    }

    public function menuTask()
    {
        return $this->belongsTo(TasksMenu::class, 'menu_task_id');
    }

}
