<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TaskLog extends Model
{
    use HasFactory;

    protected $table = 'hr_task_logs';

    // Constants for log types
    const TYPE_CREATED = 'created';
    const TYPE_MOVED = 'moved';
    const TYPE_EDITED = 'edited';
    const TYPE_REJECTED = 'rejected';
    const TYPE_COMMENTED = 'commented';
    const TYPE_IMAGES_ADDED = 'images_added';

    // Fillable attributes
    protected $fillable = [
        'task_id',
        'created_by',
        'description',
        'log_type',
        'details',
        'total_hours_taken',
    ];

    // Define the relationship with the Task model
    public function task()
    {
        return $this->belongsTo(Task::class);
    }

    // Define the relationship with the User model
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
