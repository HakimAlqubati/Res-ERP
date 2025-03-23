<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NotificationSetting extends Model
{
    protected $fillable = ['type', 'frequency', 'daily_time', 'active'];

    public const TYPE_STOCK_MIN_QUANTITY     = 'stock_min_quantity';
    public const TYPE_EMPLOYEE_FORGET        = 'employee_forget_attendance';
    public const TYPE_ABSENT_EMPLOYEES       = 'absent_employees';
    public const TYPE_TASK_SCHEDULING        = 'task_scheduling';

    public const TYPES = [
        self::TYPE_STOCK_MIN_QUANTITY,
        self::TYPE_EMPLOYEE_FORGET,
        self::TYPE_ABSENT_EMPLOYEES,
        self::TYPE_TASK_SCHEDULING,
    ];
}
