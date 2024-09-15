<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DailyTasksSettingUp extends Model
{
    use HasFactory;
    protected $table = 'daily_tasks_setting_up';

    const TYPE_SCHEDULE_DAILY = 'daily';
    const TYPE_SCHEDULE_WEEKLY = 'weekly';
    const TYPE_SCHEDULE_MONTHLY = 'monthly';
    protected $fillable = [
        'assigned_by',
        'assigned_to_users',
        'title',
        'description',
        'active',
        'menu_tasks',
        'assigned_to',
        'start_date',
        'end_date',
        'schedule_type',
    ];

    public function steps()
    {
        return $this->morphMany(TaskStep::class, 'morphable');
    }

    public function assignedto()
    {
        return $this->belongsTo(Employee::class, 'assigned_to');
    }
    public function assignedby()
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }

    public static function getScheduleTypes()
    {
        return [
            self::TYPE_SCHEDULE_DAILY => 'Daily',
            self::TYPE_SCHEDULE_WEEKLY => 'Weekly',
            self::TYPE_SCHEDULE_MONTHLY => 'Monthly',
        ];
    }
}
