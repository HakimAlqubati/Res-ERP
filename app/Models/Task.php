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
        return $this->hasMany(TaskComment::class, 'task_id');
    }

    public function attachments()
    {
        return $this->hasMany(TaskAttachment::class, 'task_id');
    }
}
