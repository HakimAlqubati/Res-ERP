<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use App\Traits\DynamicConnection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Contracts\Auditable;

class EmployeeOvertime extends Model implements Auditable
{
    use HasFactory, SoftDeletes, \OwenIt\Auditing\Auditable;
    protected $table = 'hr_employee_overtime';

    // Fillable fields for mass assignment
    protected $fillable = [
        'employee_id',
        'date',
        'start_time',
        'end_time',
        'hours',
        'rate',
        'reason',
        'approved',
        'approved_by',
        'notes',
        'created_by',
        'updated_by',
        'branch_id',
        'approved_at',
        'type',
    ];
    protected $auditInclude = [
        'employee_id',
        'date',
        'start_time',
        'end_time',
        'hours',
        'rate',
        'reason',
        'approved',
        'approved_by',
        'notes',
        'created_by',
        'updated_by',
        'branch_id',
        'approved_at',
        'type',
    ];

    // Enum values for the 'type' field
    public const TYPE_BASED_ON_MONTH = 'based_on_month';
    public const TYPE_BASED_ON_DAY = 'based_on_day';

    // Array of all possible types
    public const TYPES = [
        self::TYPE_BASED_ON_MONTH,
        self::TYPE_BASED_ON_DAY,
    ];

    public static function getTypes()
    {
        return [
            EmployeeOvertime::TYPE_BASED_ON_DAY => 'Hourly',
            EmployeeOvertime::TYPE_BASED_ON_MONTH => 'Daily',
        ];
    }
    // Relationships
    // Relationship with the Employee model
    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }

    // Relationship with the User model (for approval)
    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    // Relationship with the User model (who created the overtime entry)
    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // Relationship with the User model (who updated the overtime entry)
    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }



    protected static function booted()
    {
        if (auth()->check()) {
            if (isBranchManager()) {
                static::addGlobalScope(function (Builder $builder) {
                    $builder->where('branch_id', auth()->user()->branch_id); // Add your default query here
                });
            } elseif (isStuff()) {
                static::addGlobalScope(function (Builder $builder) {
                    $builder->where('employee_id', auth()->user()->employee->id); // Add your default query here
                });
            }
        }
    }

    public function scopeDay($query)
    {
        return $query->where('type', static::TYPE_BASED_ON_DAY);
    }
    public function scopeMonth($query)
    {
        return $query->where('type', static::TYPE_BASED_ON_MONTH);
    }

    // Accessor for the 'type' attribute
    public function getTypeValueAttribute()
    {
        $type = $this->type;
        switch ($type) {
            case self::TYPE_BASED_ON_DAY:
                $type = 'Houly';
                break;

            case self::TYPE_BASED_ON_MONTH:
                $type = 'Daily';
                break;

            default:
                $type = 'Unnkown';
                break;
        }
        return $type;
    }
}
