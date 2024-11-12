<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TaskStep extends Model
{
    use HasFactory;
    protected $table = 'hr_task_steps';
    protected $fillable = [
        'task_id',
        'title',
        'order',
        'done',
        'model',
        'model_id',
    ];

    // public function task()
    // {
    //     return $this->belongsTo(Task::class);
    // }

    public function morphable()
    {
        return $this->morphTo();
    }

   
}
