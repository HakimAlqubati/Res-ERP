<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable;

class TaskStep extends Model implements Auditable
{
    use HasFactory, \OwenIt\Auditing\Auditable;
    protected $table = 'hr_task_steps';
    protected $fillable = [
        'task_id',
        'title',
        'order',
        'done',
        'model',
        'model_id',
    ];
    protected $auditInclude = [
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
