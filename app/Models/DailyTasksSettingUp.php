<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DailyTasksSettingUp extends Model
{
    use HasFactory;
    protected $table = 'daily_tasks_setting_up';
    protected $fillable = [
        'assigned_by',
        'assigned_to_users',
        'title',
        'description',
        'active',
    ];
}
