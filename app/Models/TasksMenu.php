<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TasksMenu extends Model
{
    use HasFactory;
    protected $table = 'tasks_menu';
    protected $fillable = ['name', 'description', 'active', 'created_by', 'updated_by'];

    // You might want to define the inverse relationship here
    public function tasks()
    {
        return $this->belongsToMany(Task::class, 'hr_tasks_menus', 'menu_task_id', 'task_id');
    }
}
