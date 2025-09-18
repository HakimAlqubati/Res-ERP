<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use App\Traits\DynamicConnection;
use App\Traits\Scopes\BranchScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DailyTasksSettingUp extends Model
{
    use HasFactory, DynamicConnection,BranchScope;
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
        'branch_id',
    ];

    public function steps()
    {
        return $this->morphMany(TaskStep::class, 'morphable');
    }

    public function getStepCountAttribute()
    {
        return $this->steps?->count();
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

    public static function getScheduleTypesKeys()
    {
        return [
            self::TYPE_SCHEDULE_DAILY,
            self::TYPE_SCHEDULE_WEEKLY,
            self::TYPE_SCHEDULE_MONTHLY,
        ];
    }

    public function taskScheduleRequrrencePattern()
    {
        return $this->hasOne(TaskScheduleRequrrencePattern::class, 'task_id');
    }

    protected static function booted()
    {

        if (auth()->check()) {
            if (isBranchManager()) {
                static::addGlobalScope(function (Builder $builder) {
                    $builder->where('branch_id', auth()->user()->branch_id); // Add your default query here
                });
            } else if (isFinanceManager()) {
                static::addGlobalScope(function (Builder $builder) {
                    $builder->where('assigned_to', auth()->user()->employee->id)
                        ->orWhere('assigned_by', auth()->user()->id)
                    ; // Add your default query here
                });
            }
        }
    }
}
