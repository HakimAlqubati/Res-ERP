<?php

namespace App\Models;

use App\Traits\DynamicConnection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use OwenIt\Auditing\Contracts\Auditable;

class EmployeePeriod extends Model implements Auditable
{
    use HasFactory,DynamicConnection,\OwenIt\Auditing\Auditable;

    // Define the table name if it's not the plural of the model name
    protected $table = 'hr_employee_periods';

    // Specify primary key if not 'id'
    protected $primaryKey = 'id';

    // If timestamps are not present in the table, set to false
    public $timestamps = false;

    // Define fillable or guarded fields
    protected $fillable = [
        'employee_id',
        'period_id',
        'days',
        'created_by',
        'updated_by',
        // Add other columns if necessary
    ];
    protected $auditInclude = [
        'employee_id',
        'period_id',
        'days',
        'created_by',
        'updated_by',
        // Add other columns if necessary
    ];

    protected $casts = [
        'days' => 'array',
    ];  
    /**
     * Relationship with HrWorkPeriod (many-to-one).
     */
    public function workPeriod()
    {
        return $this->belongsTo(WorkPeriod::class, 'period_id');
    }

    /**
     * Relationship with Employee (many-to-one).
     */
    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employee_id');
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

    public function scopeForDay($query, $day)
    {
        return $query->whereJsonContains('days', $day);
    }
    public function getValDaysAttribute(){
        return $this->days;
    }
}
