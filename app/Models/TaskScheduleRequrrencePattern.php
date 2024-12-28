<?php

namespace App\Models;

use App\Traits\DynamicConnection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TaskScheduleRequrrencePattern extends Model
{
    use HasFactory, DynamicConnection;
    protected $table = 'hr_task_schedule_requrrence_pattern';

    protected $fillable = [
        'task_id', // reference to schedule tasks
        'schedule_type',
        'start_date',
        'recur_count',
        'end_date',
        'recurrence_pattern',
    ];

    // Relationship: ScheduleTask has one TaskScheduleRequrrencePattern
    public function task()
    {
        return $this->belongsTo(DailyTasksSettingUp::class, 'task_id');
    }
}
