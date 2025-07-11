<?php
namespace App\Models;

use App\Enums\DayOfWeek;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use OwenIt\Auditing\Contracts\Auditable;

class EmployeePeriodHistory extends Model implements Auditable
{
    use HasFactory, \OwenIt\Auditing\Auditable;

    protected $table    = 'hr_employee_period_histories';
    protected $fillable = [
        'employee_id',
        'period_id',
        'start_date',
        'end_date',
        'start_time',
        'end_time',
        'active',
        'period_days',
        'created_by',
        'updated_by',
        'day_of_week',
    ];
    protected $auditInclude = [
        'employee_id',
        'period_id',
        'start_date',
        'end_date',
        'start_time',
        'end_time',
        'active',
        'period_days',
        'created_by',
        'updated_by',
        'day_of_week',
    ];

    protected $casts = [
        'period_days' => 'array',
        'day_of_week' => DayOfWeek::class,

    ];
    // Define the relationship with Employee
    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    // Define the relationship with WorkPeriod
    public function workPeriod()
    {
        return $this->belongsTo(WorkPeriod::class, 'period_id');
    }
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $model->created_by = auth()->id() ?? null;
            $model->updated_by = auth()->id() ?? null;
        });

        static::updating(function ($model) {
            $model->updated_by = auth()->id() ?? null;
        });
    }
    /**
     * Get the user who created the record.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user who last updated the record.
     */
    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function scopeActive($query)
    {
        return $query->where('active', true);
    }
}