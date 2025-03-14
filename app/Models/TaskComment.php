<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Contracts\Auditable;

class TaskComment extends Model implements Auditable
{
    use SoftDeletes, \OwenIt\Auditing\Auditable;

    protected $table = 'hr_task_comments';

    protected $fillable = [
        'task_id',
        'user_id',
        'comment',
    ];
    protected $auditInclude = [
        'task_id',
        'user_id',
        'comment',
    ];

    public function task()
    {
        return $this->belongsTo(Task::class, 'task_id');
    }

    public function photos(){
        return $this->hasMany(TaskCommentPhoto::class,'comment_id');
    }

    public function getPhotosCountAttribute()
    {
        return $this->photos()->count();
    }
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
